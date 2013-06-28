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
use RGeyer\Guzzle\Rs\Model\Mc\Deployment;
use RGeyer\Guzzle\Rs\Common\ClientFactory;


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
   *    'model' => \stdClass # The security group representation as defined in the output json schema
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
   * @var array An associative array where the key is the string ID of the \SelfService\Entity\ProvisionedServer and the value is an array of \RGeyer\Guzzle\Rs\Model\Mc\Server which was provisioned by the helper.
   */
  protected $_servers;

  /**
   * @var \RGeyer\Guzzle\Rs\Model\ServerArray[] The complete list of server arrays provisioned by this helper
   */
  protected $_arrays;

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
   * @param String $cloud_id The integer ID of the cloud
   * @param String $name The desired name for a new key if it must be created
   * @param array $prov_prod A reference to an array which will have an RGeyer\Guzzle\Rs\Model\Mc\SshKey appended to it if this method creates a new key.  # TODO: This is super naughty and should be stopped
   * @return bool|\RGeyer\Guzzle\Rs\Model\Mc\SshKey Boolean false if the cloud does not support SSH keys, an RGeyer\Guzzle\Rs\Model\Mc\SshKey otherwise
   */
	protected function _getSshKey($cloud_id, $name, &$prov_prod) {
    $name = sprintf("%s-%s", $name, $this->_now_ts);
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
   * @param \stdClass[] $groups An array of stdClass representations of a SecurityGroup (as defined in the output json schema) for which you'd like hrefs
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
   * @param stdClass $server The server representation as defined in the output json schema
   * @param \RGeyer\Guzzle\Rs\Model\Mc\Deployment $deployment The previously provisioned deployment the server(s) should be created in
   * @param array $inputs An associative array of inputs specified for the server
   * where the key is the name of the input and the value is the value of the input.
   * @return array A mix of RGeyer\Guzzle\Rs\Model\Mc\Server and RGeyer\Guzzle\Rs\Model\Mc\SshKey objects which were created during the process
   */
  public function provisionServer($server, Deployment $deployment, array $inputs = array()) {
    $result = array();
    $cloud_id = \RGeyer\Guzzle\Rs\RightScaleClient::getIdFromRelativeHref($server->instance->cloud_href);

    $st = $this->_findOrImportServerTemplate(
      $server->instance->server_template->name,
      $server->instance->server_template->revision,
      $server->instance->server_template->publication_id
    );

    $mci_href = null;
    $instance_type_href = null;
    $this->_selectMciAndInstanceType($st, $cloud_id, $mci_href, $instance_type_href);

    $datacenter_hrefs = array();
    if($this->_clouds[$cloud_id]->supportsCloudFeature('datacenters')) {
      if($server->instance->datacenter_href) {
        $datacenter_hrefs = $server->instance->datacenter_href;
      } else {
        $datacenters = $this->api_cache->getDatacenters($cloud_id);
        foreach($datacenters as $datacenter) {
          # TODO: Filter by datacenters specified as defaults for product, or
          # specified as a meta input by the user
          $datacenter_hrefs[] = $datacenter->href;
        }
      }
    }

    $server_secgrps = $this->_getProvisionedSecurityGroupHrefs($server->instance->security_groups);

    $this->log->info("About to provision " . $server->count . " servers of type " . $server->name_prefix);

    for ($i=1; $i <= $server->count; $i++) {
      $nickname = $server->name_prefix;
      if($server->count > 1) {
        $nickname .= $i;
      }

      $params = array();
      $api_server = $this->client->newModel('Server');

      $instance_href = $server->instance->instance_type_href ?: $instance_type_href;

      $params['server[name]'] = $nickname;
      $params['server[deployment_href]'] = $deployment->href;
      $params['server[instance][server_template_href]'] = $st->href;
      $params['server[instance][cloud_href]'] = $this->_clouds[$cloud_id]->href;
      $params['server[instance][instance_type_href]'] = $instance_href;
      if(count($server_secgrps) > 0) {
        $params['server[instance][security_group_hrefs]'] = $server_secgrps;
      }
      if(count($datacenter_hrefs) > 0) {
        $params['server[instance][datacenter_href]'] = $datacenter_hrefs[$i % count($datacenter_hrefs)];
      }
      $params['server[instance][multi_cloud_image_href]'] = $mci_href;
      $ssh_key = $this->_getSshKey( $cloud_id, $deployment->name, $result);
      if($ssh_key) {
        $params['server[instance][ssh_key_href]'] = $ssh_key->href;
      }
      if(property_exists($server, 'optimized')) {
        $params['server[optimized]'] = $server->optimized ? 'true' : 'false';
      }

      $api_server->cloud_id = $cloud_id;
      $api_server->create($params);
      $api_server->addTags($this->_tags);


      if(count($inputs) > 0) {
        $api_server->find_by_href($api_server->href);
        $next_instance_href = null;
        foreach($api_server->links as $link) {
          if($link->rel == "next_instance") {
            $next_instance_href = $link->href;
          }
        }
        if($next_instance_href) {
          $command = $this->client->getCommand('inputs_multi_update',
            array(
              'path' => str_replace('/api/', '', $next_instance_href).'/inputs/multi_update',
              'inputs' => $inputs
            )
          );
          $command->execute();
          $command->getResult();
        }
      }

      $result[] = $api_server;
      $this->_servers[strval($server->id)][] = $api_server;

      $this->log->info(sprintf("Created Server - Name: %s ID: %s", $server->name_prefix, $api_server->id));
      if($server->alert_specs) {
        foreach($server->alert_specs as $alert) {
          $this->_provisionAlertSpec($alert, $api_server->href);
        }
      }
    }
    return $result;
  }

  /**
   * Creates a new deployment, using RightScale API 1.5
   *
   * @param string $name The name for the deployment
   * @param string|null $description A description for the deployment, or null
   * @param array $inputs An associative array of inputs specified for the deployment
   * @param string $server_tag_scope Either "deployment" or "account". Default is "deployment"
   * where the key is the name of the input and the value is the value of the input.
   * @return \RGeyer\Guzzle\Rs\Model\Mc\Deployment The newly created deployment
   */
  public function provisionDeployment($name, $description = null, array $inputs = array(), $server_tag_scope = 'deployment') {
    $deployment = $this->client->newModel('Deployment');
    $deployment->name = $name;
    $deployment->server_tag_scope = $server_tag_scope;
    if($description) {
      $deployment->description = $description;
    }
    $deployment->create();

    $command = $this->client->getCommand('tags_multi_add',
      array(
        'resource_hrefs' => array($deployment->href),
        'tags' => $this->_tags
      )
    );
    $command->execute();
    $command->getResult();

    $this->updateDeployment($deployment, $inputs);

    $this->log->info(sprintf("Created Deployment - Name: %s href: %s", $name, $deployment->href));

    return $deployment;
  }

  /**
   * @param \RGeyer\Guzzle\Rs\Model\Mc\Deployment $deployment A RightScaleAPIClient model representing the provisioned deployment, only the ->href property is used
   * @param array $inputs An associative array of inputs specified for the deployment
   * where the key is the name of the input and the value is the value of the input.
   * @return void
   */
  public function updateDeployment($deployment, $inputs) {
    if(count($inputs) > 0) {
      $command = $this->client->getCommand('inputs_multi_update',
        array(
          'path' => str_replace('/api/', '', $deployment->href).'/inputs/multi_update',
          'inputs' => $inputs
        )
      );
      $command->execute();
      $command->getResult();
    }
  }

  /**
   * Creates a new security group, using RightScale API 1.5 as appropriate.
   *
   * @param \stdClass $security_group The security group representation as defined in the output json schema
   * @return bool|\RGeyer\Guzzle\Rs\Model\Mc\SecurityGroup False if no group was created, a created security group if successful
   */
  public function provisionSecurityGroup($security_group) {
    $security_group->name = sprintf("%s-%s", $security_group->name, $this->_now_ts);
    $cloud_id = RightScaleClient::getIdFromRelativeHref($security_group->cloud_href);
    // Check if this cloud supports security groups first!
    if(!$this->_clouds[$cloud_id]->supportsCloudFeature('security_groups')) {
      $this->log->debug('The specified cloud (' . $cloud_id . ') does not support security groups, skipping the creation of the security group');
      return false;
    }

    $secGrp = null;
    $secGrp = $this->client->newModel('SecurityGroup');
    $secGrp->name = $security_group->name;
    if($security_group->description) {
      $secGrp->description = $security_group->description;
    }
    $secGrp->cloud_id = $cloud_id;
    $secGrp->create();
    // This feels a bit hacky?
    $secGrp->find_by_id ( $secGrp->id );

    $this->_security_groups[$security_group->id] = array(
      'api' => $secGrp,
      'model' => $security_group
    );

    $this->log->info(sprintf("Created Security Group - Name: %s ID: %s", $security_group->name, $secGrp->id));

    return $secGrp;
  }

  /**
   * Adds the specified rules to a security group.  This is separate from the creation of the
   * security group since groups may reference each other, meaning all groups must be created
   * first, then rules can be added later.
   *
   * @param \stdClass $security_group The security group representation as defined in the output json schema
   * @return bool
   */
  public function provisionSecurityGroupRules($security_group) {
    $cloud_id = RightScaleClient::getIdFromRelativeHref($security_group->cloud_href);
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

    $this->log->info("About to provision " . count($security_group->security_group_rules) . " rules for Security Group " . $security_group->name);
    foreach ( $security_group->security_group_rules as $rule ) {
      $other_model = null; // Declare this up here so it can be accessed when logging at the end
      if($rule->source_type == "group") {
        if(array_key_exists($rule->ingress_group->id, $this->_security_groups)) {
          $ingress_group = $this->_security_groups[$rule->ingress_group->id];

          $other_model = $ingress_group['model'];
          $other_api = $ingress_group['api'];

          $api->createGroupRule(
            $other_model->name,
            $owner,
            $rule->protocol,
            $rule->protocol_details->start_port,
            $rule->protocol_details->end_port
          );
        } else {
          $this->log->warn(sprintf("No concrete security group was provisioned for security group doctrine model ID %s.  Skipping rule creation", $rule->ingress_group->id));
          continue;
        }
      } else {
        $api->createCidrRule(
          $rule->protocol,
          $rule->cidr_ips,
          $rule->protocol_details->start_port,
          $rule->protocol_details->end_port
        );
      }

      $this->log->info(
        sprintf("Created Security Group Rule - Group Name: %s Rule: protocol: %s ports: %s..%s %s: %s",
          $security_group->name,
          $rule->protocol,
          $rule->protocol_details->start_port,
          $rule->protocol_details->end_port,
          $rule->source_type,
          $rule->source_type == "group" ? $other_model->name : $rule->cidr_ips
        )
      );
    }
    return true;
  }

  public function provisionServerArray($array, Deployment $deployment, array $inputs = array()) {
    $result = array();
    $cloud_id = \RGeyer\Guzzle\Rs\RightScaleClient::getIdFromRelativeHref($array->instance->cloud_href);

    $st = $this->_findOrImportServerTemplate(
      $array->instance->server_template->name,
      $array->instance->server_template->revision,
      $array->instance->server_template->publication_id
    );

    $mci_href = null;
    $instance_type_href = null;
    $this->_selectMciAndInstanceType($st, $cloud_id, $mci_href, $instance_type_href);

    $secgrps = $this->_getProvisionedSecurityGroupHrefs($array->instance->security_groups);

    # TODO: Handle queue or alert based arrays. Only alert at the moment
    $params = array(
      'server_array[name]' => $array->name,
      'server_array[state]' => $array->state ?: 'disabled',
      'server_array[array_type]' => $array->array_type,
      'server_array[elasticity_params][bounds][max_count]' => strval($array->elasticity_params->bounds->max_count) ?: '10',
      'server_array[elasticity_params][bounds][min_count]' => strval($array->elasticity_params->bounds->min_count) ?: '2',
      'server_array[elasticity_params][pacing][resize_calm_time]' => strval($array->elasticity_params->pacing->resize_calm_time) ?: "10",
      'server_array[elasticity_params][pacing][resize_up_by]' => strval($array->elasticity_params->pacing->resize_up_by) ?: "3",
      'server_array[elasticity_params][pacing][resize_down_by]' => strval($array->elasticity_params->pacing->resize_down_by) ?: "1",
      'server_array[deployment_href]' => $deployment->href,
      'server_array[instance][cloud_href]' => $this->_clouds[$cloud_id]->href,
      'server_array[instance][server_template_href]' => $st->href,
      'server_array[instance][multi_cloud_image_href]' => $mci_href,
      'server_array[instance][instance_type_href]' => $instance_type_href,
    );
    if(property_exists($array, 'optimized')) {
      $params['server_array[optimized]'] = $array->optimized ? 'true' : 'false';
    }
    if($array->array_type == "alert") {
      $params = array_merge($params, array(
        'server_array[elasticity_params][alert_specific_params][decision_threshold]' => strval($array->elasticity_params->alert_specific_params->decision_threshold) ?: '51',
        'server_array[elasticity_params][alert_specific_params][voters_tag_predicate]' => $array->elasticity_params->alert_specific_params->voters_tag_predicate
      ));
    }
    if(count($secgrps) > 0) {
      $params['server_array[instance][security_group_hrefs]'] = $secgrps;
    }
    $ssh_key = $this->_getSshKey($cloud_id, $deployment->name, $result);
    if($ssh_key) {
      $params['server_array[instance][ssh_key_href]'] = $ssh_key->href;
    }
    /* TODO: Duplicating the UI behavior here, in that if DC hrefs are specified
     * but there is only one, we ignore it since it's not allowed.  This is pending
     * some research and response from product
     */
    if($array->instance->datacenter_href &&
       count($array->instance->datacenter_href) > 1) {
      $datacenter_count = count($array->instance->datacenter_href);
      $each = intval(100/$datacenter_count);
      $remainder = intval(100%$datacenter_count);
      $params['server_array[datacenter_policy]'] = array();
      foreach($array->instance->datacenter_href as $idx => $dc_href) {
        $this_dc_policy = array(
          'datacenter_href' => $dc_href,
          'max' => 0,
          'weight' => $each
        );
        if($idx == 0 && $remainder != 0) {
          $this_dc_policy['weight'] = $each+$remainder;
        }
        $params['server_array[datacenter_policy]'][] = $this_dc_policy;
      }
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

    if(count($inputs) > 0) {
      $api_array->find_by_href($api_array->href);
      $next_instance_href = null;
      foreach($api_array->links as $link) {
        if($link->rel == "next_instance") {
          $next_instance_href = $link->href;
        }
      }
      if($next_instance_href) {
        $command = $this->client->getCommand('inputs_multi_update',
          array(
            'path' => str_replace('/api/', '', $next_instance_href).'/inputs/multi_update',
            'inputs' => $inputs
          )
        );
        $command->execute();
        $command->getResult();
      }
    }

    $result[] = $api_array;
    $this->_arrays[strval($array->id)] = $api_array;
    $this->log->info(sprintf("Created Array - Name: %s ID: %s", $array->name, $api_array->id));
    if($array->alert_specs) {
      foreach($array->alert_specs as $alert) {
        $this->_provisionAlertSpec($alert, $api_array->href);
      }
    }
    return $result;
  }

  public function _provisionAlertSpec($alert, $subject_href) {
    $api_alert_spec = $this->client->newModel('AlertSpec');
    $api_alert_spec->name = $alert->name;
    $api_alert_spec->file = $alert->file;
    $api_alert_spec->variable = $alert->variable;
    $api_alert_spec->condition = $alert->condition;
    $api_alert_spec->threshold = $alert->threshold;
    $api_alert_spec->duration = $alert->duration;
    $api_alert_spec->subject_href = $subject_href;
    if($alert->vote_tag && $alert->vote_type) {
      $api_alert_spec->vote_tag = $alert->vote_tag;
      $api_alert_spec->vote_type = $alert->vote_type;
    } else if ($alert->escalation_name) {
      $api_alert_spec->escalation_name = $alert->escalation_name;
    }
    $api_alert_spec->create();
    $this->log->info(sprintf("Created Alert Spec - Name: %s For Subject: %s", $api_alert_spec->name, $subject_href));
  }

  public function launchServers() {
    foreach($this->_servers as $servers) {
      foreach($servers as $server) {
        $server->launch();
      }
    }
  }

}
