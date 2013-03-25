<?php
/*
Copyright (c) 2012-2013 Ryan J. Geyer <me@ryangeyer.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SelfService\Service;

use RGeyer\Guzzle\Rs\RightScaleClient;
use RGeyer\Guzzle\Rs\Common\ClientFactory;
use RGeyer\Guzzle\Rs\Model\Mc\Deployment;

use Doctrine\ORM\PersistentCollection;

use SelfService\Entity\Provisionable\Server;
use SelfService\Entity\Provisionable\ServerArray;
use SelfService\Entity\Provisionable\SecurityGroup;
use SelfService\Entity\Provisionable\ServerTemplate as OrmServerTemplate;

use RGeyer\Guzzle\Rs\Model\Mc\ServerTemplate as ApiServerTemplate;

class ProvisioningHelper {

  /**
   * A RightScale 1.5 API client.  This is public to allow mocking for unit testing. Likely
   * won't want to much with this much
   * @var \RGeyer\Guzzle\Rs\RightScaleClient
   */
  public $client;

  /**
   * @var \SelfService\Service\RightScaleAPICache
   */
  public $api_cache;

  /**
   * @var int The current timestamp, used for appending to the names of provisioned resources
   */
  protected $_now_ts;

  /**
   * @var \Zend\Log\Logger
   */
  protected $log;

  /**
   * In the form;
   *
   * Where the index is the primary key of the security group model in the RSSS database
   *
   * array(
   *  '12345' => array(
   *    'api' => \RGeyer\Guzzle\Rs\Model\AbstractSecurityGroup,
   *    'model' => \SecurityGroup # The doctrine ORM model
   * );
   *
   * @var array An associative array (hash) of security groups.
   */
  protected $_security_groups = array();

  /**
   * An associative array (hash) of clouds as returned by \RGeyer\Guzzle\Rs\Model\Cloud::indexAsHash
   *
   * @var array An associative array (hash) of clouds as returned by \RGeyer\Guzzle\Rs\Model\Cloud::indexAsHash
   */
  protected $_clouds;

  public function setClouds(array $clouds = array()) {
    $this->_clouds = $clouds;
  }

  public function getClouds() {
    return $this->_clouds;
  }

  /**
   * @var array An associative array (hash) of cloud "owner" ID's where the key is the cloud_id, and the value is the owner
   */
  protected $_owners;

  /**
   * @var \RGeyer\Guzzle\Rs\Model\Ec2\ServerTemplate[] The response of the RightScale API Client 1.0 servertemplate index
   */
  protected $_server_templates;

  /**
	 * An array of SshKey API models provisioned for a provision product request
	 *
	 * @var \RGeyer\Guzzle\Rs\Model\Ec2\SshKey[]
	 */
  protected $_ssh_keys = array();

  /**
   * @var \RGeyer\Guzzle\Rs\Model\AbstractServer[] The complete list of servers provisioned by this helper
   */
  protected $_servers;

  /**
   * @var array An array of tags which will be set on every taggable resource created by this provisioning helper
   */
  protected $_tags = array();

  /**
   * @var string The RightScale Account ID
   */
  protected $_rs_acct;

  /**
   * @param array $tags The array of tags which will be set on every taggable resource created by this provisioning helper
   */
  public function setTags(array $tags) {
    $this->_tags = $tags;
  }

  public function __construct($sm, $log, $owners = array()) {
    $config = $sm->get('Configuration');
    $this->_rs_acct = $config['rsss']['cloud_credentials']['rightscale']['account_id'];
    $this->_now_ts = time();
    $this->log = $log;
    $this->client = $sm->get('RightScaleAPIClient');
    $this->api_cache = $sm->get('RightScaleAPICache');

    # TODO: This is a direct copy/paste from \RGeyer\Guzzle\Rs\Model\Mc\Cloud
    # Should refactor this somehow, or have the APICache store/fetch something
    # in the hash format.
    $clouds = $this->api_cache->getClouds();
    $hash = array();
		foreach($clouds as $cloud) {
			$hash[$cloud->id] = $cloud;
		}

    $this->_clouds = $hash;
    $this->_owners = $owners;

    $this->_server_templates = $this->api_cache->getServerTemplates();
  }

  /**
   * Fetches or JIT creates an SSH key for the specified cloud.
   *
   * @param $cloud_id The integer ID of the cloud
   * @param $name The desred name for a new key if it must be created
   * @param $prov_prod A reference to an array which will have an RGeyer\Guzzle\Rs\Model\Mc\SshKey appended to it if this method creates a new key.  # TODO: This is super naughty and should be stopped
   * @return bool|RGeyer\Guzzle\Rs\Model\Mc\SshKey Boolean false if the cloud does not support SSH keys, an RGeyer\Guzzle\Rs\Model\Mc\SshKey otherwise
   */
	protected function _getSshKey($cloud_id, $name, &$prov_prod) {
    if($this->_clouds[$cloud_id]->supportsCloudFeature('ssh_keys')) {
      if(!array_key_exists($cloud_id, $this->_ssh_keys)) {
        $newkey = $this->client->newModel('SshKey');
        // TODO: For now assume we're creating a new SSH key and reusing
        // it, but this should really be configurable
        $newkey->name = $name;
        $newkey->cloud_id = $cloud_id;
        $newkey->create();

        # TODO: This is super sneaky and naughty and dirty.  Need a better mechanism
        # for creating SSH keys and persisting their ID in the DB.
        $prov_prod[] = $newkey;

        $this->log->info(sprintf("Created SSH Key - Cloud: %s Name: %s ID: %s", $cloud_id, $name, $newkey->id));
        $this->_ssh_keys[$cloud_id] = $newkey;
      }

      return $this->_ssh_keys[$cloud_id];
    } else {
      return false;
    }
	}

  /**
   * @throws \InvalidArgumentException when the template cannot be found or imported
   * @param $nickname
   * @param $version
   * @param $publication_id
   * @return null|RGeyer\Guzzle\Rs\Model\Mc\ServerTemplate
   */
  protected function _findOrImportServerTemplate($nickname, $version, $publication_id) {
    $st = null;
    foreach( $this->_server_templates as $api_st ) {
      if (strtolower(trim($api_st->name)) == strtolower(trim($nickname)) &&
          $api_st->revision == $version) {
        $st = $api_st;
      }
    }

    # Try to import it
    if(!$st) {
      # TODO: Handle the scenario where the template can not be imported (500)
      $command = $this->client->getCommand(
        'publications_import',
        array(
          'id' => $publication_id
        )
      );
      $command->execute();

      $mc_href = (string)$command->getResponse()->getHeader('Location');
      $st = $this->client->newModel('ServerTemplate');
      $st->find_by_href($mc_href);
      $this->_server_templates = $this->api_cache->updateServerTemplates();
    }

    if (!$st) {
      throw new \InvalidArgumentException('A server template with nickname "' . $nickname . '" and version "' . $version . '" was not found!');
    }

    # TODO: Ideally wait for the ST to be imported here, not sure there is a mechanism for that tho?
    return $st;
  }

  /**
   * @throws \InvalidArgumentException if the specified ServerTemplate does not support the specified cloud
   * @param \RGeyer\Guzzle\Rs\Model\Mc\ServerTemplate $st
   * @param $cloud_id
   * @param $mci_href A reference variable which gets set to the HREF of a supported MCI
   * @param $instance_type_href A reference variable which gets set to the HREF of a supported instance type
   * @return void
   */
  protected function _selectMciAndInstanceType(ApiServerTemplate $st, $cloud_id, &$mci_href, &$instance_type_href) {
    # Detect the right MultiCloudImage and select it, or throw an exception if there isn't a match
    $mci_href = null;
    $instance_type_href = null;
    $images = $st->multi_cloud_images();
    foreach($images as $mci) {
      foreach($mci->settings() as $setting) {
        $cloud_link = array_filter($setting->links, function($var) {
          return $var->rel == 'cloud';
        });
        $mci_cloud_href = array_pop($cloud_link)->href;
        $cloud_href = $this->_clouds[$cloud_id]->href;
        if($mci_cloud_href == $cloud_href) {
          $mci_href = $mci->href;
          $instance_link = array_filter($setting->links, function($var) {
              return $var->rel == 'instance_type';
          });
          if(count($instance_link) > 0) {
            $instance_type_href = array_pop($instance_link)->href;
          }
          break 2;
        }
      }
    }
    if(!$mci_href) {
      $message = sprintf('The ServerTemplate "%s" does not have an image which supports the cloud "%s"', $st->name, $this->_clouds[$cloud_id]->name);
      throw new \InvalidArgumentException($message);
    }

    if(!$instance_type_href) {
      $instance_types = $this->api_cache->getInstanceTypes($cloud_id);
      $instance_type = array_pop($instance_types);
      $instance_type_href = $instance_type->href;
      # TODO: Maybe allow some metadata for a "default" for private clouds.
    }
  }

  /**
   * @param array|Doctrine\ORM\PersistentCollection of \SelfService\Entity\Provisionable\SecurityGroup[] $groups A list of provisionable groups for which you'd like hrefs
   * @return String[] the href for each provisioned security group
   */
  protected function _getProvisionedSecurityGroupHrefs($groups) {
    $secgrps = array();
    foreach ( $groups as $secgrp ) {
      // This is as good as checking Cloud::supportsSecurityGroups because $this->_security_groups only
      // contains security groups for clouds where they're supported, and have already been created in the
      // ProvisioningHelper::provisionSecurityGroup method
      if (array_key_exists( $secgrp->id, $this->_security_groups )) {
        $secgrps[] = $this->_security_groups[$secgrp->id]['api']->href;
      }
    }
    return $secgrps;
  }

  /**
   * Provisions one or many Servers of the specified type
   *
   * @throws \InvalidArgumentException if there are ServerTemplate discovery or import issues.
   * @param SelfService\Entity\Provisionable\Server $server An rsss Doctrine ORM Model describing the desired server.
   * @param \RGeyer\Guzzle\Rs\Model\Mc\Deployment $deployment The previously provisioned deployment the server(s) should be created in
   * @return array A mix of RGeyer\Guzzle\Rs\Model\Mc\Server and RGeyer\Guzzle\Rs\Model\Mc\SshKey objects which were created during the process
   */
  public function provisionServer(Server $server, Deployment $deployment) {
    $result = array();
    $cloud_id = $server->cloud_id->getVal();

    $st = $this->_findOrImportServerTemplate(
      $server->server_template->nickname->getVal(),
      $server->server_template->version->getVal(),
      $server->server_template->publication_id->getVal()
    );

    $mci_href = null;
    $instance_type_href = null;
    $this->_selectMciAndInstanceType($st, $cloud_id, $mci_href, $instance_type_href);

    # TODO: Handle all defaulted things such as datacenters
    $server_secgrps = $this->_getProvisionedSecurityGroupHrefs($server->security_groups);

    $this->log->info("About to provision " . $server->count->getVal() . " servers of type " . $server->nickname->getVal());

    for ($i=1; $i <= $server->count->getVal(); $i++) {
      $nickname = $server->nickname->getVal();
      if($server->count->getVal() > 1) {
        $nickname .= $i;
      }

      $params = array();
      $api_server = $this->client->newModel('Server');

      $params['server[name]'] = $nickname;
      $params['server[deployment_href]'] = $deployment->href;
      $params['server[instance][server_template_href]'] = $st->href;
      $params['server[instance][cloud_href]'] = $this->_clouds[$cloud_id]->href;
      $params['server[instance][instance_type_href]'] = $server->instance_type != null ? $server->instance_type->getVal() : $instance_type_href;
      if(count($server_secgrps) > 0) {
        $params['server[instance][security_group_hrefs]'] = $server_secgrps;
      }
      $params['server[instance][multi_cloud_image_href]'] = $mci_href;
      $ssh_key = $this->_getSshKey( $cloud_id, $deployment->name, $result);
      if($ssh_key) {
        $params['server[instance][ssh_key_href]'] = $ssh_key->href;
      }

      # TODO: Add instance type href and randomized (or HA best practice'd) datacenter href here

      $api_server->cloud_id = $cloud_id;
      $api_server->create($params);
      $api_server->addTags($this->_tags);

      $result[] = $api_server;
      $this->_servers[] = $api_server;

      $this->log->info(sprintf("Created Server - Name: %s ID: %s", $server->nickname->getVal(), $api_server->id));
    }
    return $result;
  }

  /**
   * Creates a new deployment, using RightScale API 1.0
   *
   * @param $params An array of parameters to pass to the call.
   * @link http://reference.rightscale.com/api1.5/resources/ResourceDeployments.html#create
   * @return RGeyer\Guzzle\Rs\Model\Mc\Deployment The newly created deployment
   */
  public function provisionDeployment($params) {
    $deployment = $this->client->newModel('Deployment');
    $deployment->create($params);

    $command = $this->client->getCommand('tags_multi_add',
      array(
        'resource_hrefs' => array($deployment->href),
        'tags' => $this->_tags
      )
    );
    $command->execute();
    $command->getResult();
    return $deployment;
  }

  /**
   * Creates a new security group, using RightScale API 1.5 as appropriate.
   *
   * @param SelfService\Entity\Provisionable\SecurityGroup $security_group The Doctrine model describing the desired security group
   * @return bool|\RGeyer\Guzzle\Rs\Model\Mc\SecurityGroup False if no group was created, a created security group if successful
   */
  public function provisionSecurityGroup(SecurityGroup $security_group) {
    $cloud_id = $security_group->cloud_id->getVal();
    // Check if this cloud supports security groups first!
    if(!$this->_clouds[$cloud_id]->supportsCloudFeature('security_groups')) {
      $this->log->debug('The specified cloud (' . $cloud_id . ') does not support security groups, skipping the creation of the security group');
      return false;
    }

    $secGrp = null;
    $secGrp = $this->client->newModel('SecurityGroup');
    $secGrp->name = $security_group->name->getVal();
    $secGrp->description = $security_group->description->getVal();
    $secGrp->cloud_id = $cloud_id;
    $secGrp->create();
    // This feels a bit hacky?
    $secGrp->find_by_id ( $secGrp->id );

    $this->_security_groups[$security_group->id] = array(
      'api' => $secGrp,
      'model' => $security_group
    );

    $this->log->info(sprintf("Created Security Group - Name: %s ID: %s", $security_group->name->getVal(), $secGrp->id));

    return $secGrp;
  }

  /**
   * Adds the specified rules to a security group.  This is separate from the creation of the
   * security group since groups may reference each other, meaning all groups must be created
   * first, then rules can be added later.
   *
   * @param SelfService\Entity\Provisionable\SecurityGroup $security_group
   * @return bool
   */
  public function provisionSecurityGroupRules(SecurityGroup $security_group) {
    $cloud_id = $security_group->cloud_id->getVal();
    // Check if this cloud supports security groups first!
    if(!$this->_clouds[$cloud_id]->supportsCloudFeature('security_groups')) {
      $this->log->debug('The specified cloud (' . $cloud_id . ') does not support security groups, skipping the creation of the security group rules');
      return false;
    }

    // Make sure this security group has been provisioned through the API, and that we have
    // the RightScale Guzzle Client Model cached, so we can add rules to it.
    if(!array_key_exists($security_group->id, $this->_security_groups)) {
      $this->log->warn(sprintf("No concrete security group was provisioned for security group doctrine model ID %s.  Can not create rules for a security group which does not exist!", $security_group->id));
      $this->log->debug(sprintf("The security group doctrine model IDs which have been provisioned by this ProvisioningHelper are (%s)", join(',', array_keys($this->_security_groups))));
      return false;
    }

    // Make sure that we can derive the owner
    if(!array_key_exists($cloud_id, $this->_owners)) {
      $this->log->warn(sprintf("Could not determine the 'owner' for cloud id %s.", $cloud_id));
      $this->log->debug(sprintf("The available clouds (and owners) is as follows.  %s", print_r($this->_owners, true)));
      return false;
    }

    $api = $this->_security_groups[$security_group->id]['api'];
    $owner = $this->_owners[$cloud_id];

    $this->log->info("About to provision " . count($security_group->rules) . " rules for Security Group " . $security_group->name->getVal());
    foreach ( $security_group->rules as $rule ) {
      $other_model = null; // Declare this up here so it can be accessed when logging at the end
      if($rule->ingress_group) {
        if(array_key_exists($rule->ingress_group->id, $this->_security_groups)) {
          $ingress_group = $this->_security_groups[$rule->ingress_group->id];

          $other_model = $ingress_group['model'];
          $other_api = $ingress_group['api'];

          $api->createGroupRule(
            $other_model->name->getVal(),
            $owner,
            $rule->ingress_protocol->getVal(),
            $rule->ingress_from_port->getVal(),
            $rule->ingress_to_port->getVal()
          );
        } else {
          $this->log->warn(sprintf("No concrete security group was provisioned for security group doctrine model ID %s.  Skipping rule creation", $rule->ingress_group->id));
          continue;
        }
      } else {
        $api->createCidrRule(
          $rule->ingress_protocol->getVal(),
          $rule->ingress_cidr_ips->getVal(),
          $rule->ingress_from_port->getVal(),
          $rule->ingress_to_port->getVal()
        );
      }

      $this->log->info(
        sprintf("Created Security Group Rule - Group Name: %s Rule: protocol: %s ports: %s..%s %s: %s",
          $security_group->name->getVal(),
          $rule->ingress_protocol->getVal(),
          $rule->ingress_from_port->getVal(),
          $rule->ingress_to_port->getVal(),
          $rule->ingress_group ? 'group' : 'IPs',
          $rule->ingress_group ? $other_model->name->getVal() : $rule->ingress_cidr_ips->getVal()
        )
      );
    }
    return true;
  }

  public function provisionServerArray(ServerArray $array, Deployment $deployment) {
    $result = array();
    $cloud_id = $array->cloud_id->getVal();

    $st = $this->_findOrImportServerTemplate(
      $array->server_template->nickname->getVal(),
      $array->server_template->version->getVal(),
      $array->server_template->publication_id->getVal()
    );

    $mci_href = null;
    $instance_type_href = null;
    $this->_selectMciAndInstanceType($st, $cloud_id, $mci_href, $instance_type_href);

    $secgrps = $this->_getProvisionedSecurityGroupHrefs($array->security_groups);

    $params = array(
      'server_array[name]' => $array->nickname->getVal(),
      'server_array[state]' => 'disabled',
      # TODO: These params assume an alert type, might want to be smarter or
      # not allow any type besides alert
      'server_array[array_type]' => $array->type->getVal(),
      'server_array[elasticity_params][alert_specific_params][decision_threshold]' => '51',
      'server_array[elasticity_params][alert_specific_params][voters_tag_predicate]' => $array->tag->getVal(),
      'server_array[elasticity_params][pacing][resize_calm_time]' => '10',
      'server_array[elasticity_params][pacing][resize_up_by]' => '3',
      'server_array[elasticity_params][pacing][resize_down_by]' => '1',
      'server_array[elasticity_params][bounds][max_count]' => strval($array->max_count->getVal()),
      'server_array[elasticity_params][bounds][min_count]' => strval($array->min_count->getVal()),
      'server_array[deployment_href]' => $deployment->href,
      'server_array[instance][cloud_href]' => $this->_clouds[$cloud_id]->href,
      'server_array[instance][server_template_href]' => $st->href,
      'server_array[instance][multi_cloud_image_href]' => $mci_href,
      'server_array[instance][instance_type_href]' => $instance_type_href,
    );
    if(count($secgrps) > 0) {
      $params['server_array[instance][security_group_hrefs]'] = $secgrps;
    }
    $ssh_key = $this->_getSshKey($cloud_id, $deployment->name, $result);
    if($ssh_key) {
      $params['server_array[instance][ssh_key_href]'] = $ssh_key->href;
    }

    $api_array = $this->client->newModel('ServerArray');
    $api_array->create($params);
    $command = $this->client->getCommand('tags_multi_add',
      array(
        'resource_hrefs' => array($api_array->href),
        'tags' => $this->_tags
      )
    );
    $command->execute();
    $command->getResult();

    $result[] = $api_array;
    $this->log->info(sprintf("Created Array - Name: %s ID: %s", $array->nickname->getVal(), $api_array->id));
    return $result;
  }

  public function launchServers() {
    foreach($this->_servers as $server) {
      $server->launch();
    }
  }

}
