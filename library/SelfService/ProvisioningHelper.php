<?php
/**
 * Created by IntelliJ IDEA.
 * User: Ryan J. Geyer
 * Date: 8/23/12
 * Time: 6:01 PM
 * To change this template use File | Settings | File Templates.
 */

namespace SelfService;

use RGeyer\Guzzle\Rs\RightScaleClient;
use RGeyer\Guzzle\Rs\Common\ClientFactory;
use RGeyer\Guzzle\Rs\Model\Ec2\Deployment;

class ProvisioningHelper {
  /**
   * A RightScale 1.0 API client.  This is public to allow mocking for unit testing. Likely
   * won't want to much with this much
   * @var RGeyer\Guzzle\Rs\RightScaleClient
   */
  public $client_ec2;

  /**
   * A RightScale 1.5 API client.  This is public to allow mocking for unit testing. Likely
   * won't want to much with this much
   * @var RGeyer\Guzzle\Rs\RightScaleClient
   */
  public $client_mc;

  /**
   * @var int The current timestamp, used for appending to the names of provisioned resources
   */
  protected $_now_ts;

  /**
   * @var Zend_Log
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
   * An associative array (hash) of clouds as returned by RGeyer\Guzzle\Rs\Model\Cloud::indexAsHash
   *
   * @var array An associative array (hash) of clouds as returned by RGeyer\Guzzle\Rs\Model\Cloud::indexAsHash
   */
  protected $_clouds;

  protected $_owners;

  /**
   * @var RGeyer\Guzzle\Rs\Model\Ec2\ServerTemplate[] The response of the RightScale API Client 1.0 servertemplate index
   */
  protected $_server_templates;

  /**
	 * An array of SshKey API models provisioned for a provision product request
	 *
	 * @var RGeyer\Guzzle\Rs\Model\Ec2\SshKey[]
	 */
  protected $_ssh_keys = array();

  /**
   * @var RGeyer\Guzzle\Rs\Model\AbstractServer[] The complete list of servers provisioned by this helper
   */
  protected $_servers;

  /**
   * @var array An array of tags which will be set on every taggable resource created by this provisioning helper
   */
  protected $_tags = array();

  /**
   * @param array $tags The array of tags which will be set on every taggable resource created by this provisioning helper
   */
  public function setTags(array $tags) {
    $this->_tags = $tags;
  }

  public function __construct($rs_account, $rs_email, $rs_password, $log, $owners = array()) {
    $this->_now_ts = time();
    $this->log = $log;
    ClientFactory::setCredentials($rs_account, $rs_email, $rs_password);
    $this->client_ec2 = ClientFactory::getClient();
    $this->client_mc = ClientFactory::getClient('1.5');

    $this->_clouds = $this->client_mc->newModel('Cloud')->indexAsHash();
    $this->_owners = $owners;

    $this->_server_templates = $this->client_mc->newModel('ServerTemplate')->index();
  }

	protected function _getSshKey($cloud_id, $name, &$prov_prod) {
		if(!array_key_exists($cloud_id, $this->_ssh_keys)) {
			$newkey = new \RGeyer\Guzzle\Rs\Model\Ec2\SshKey();
			// TODO: For now assume we're creating a new SSH key and reusing
			// it, but this should really be configurable

			// TODO: Need to refactor this into a lazy load function for creating/fetching a key for the
			// specified cloud, including error handling for clouds which don't support SSH keys
			$newkey->aws_key_name = $name;
			$newkey->cloud_id = $cloud_id;
			$newkey->create();
      $prov_prod[] = $newkey;

			$this->log->info(sprintf("Created SSH Key - Cloud: %s Name: %s ID: %s", $cloud_id, $name, $newkey->id));
			$this->_ssh_keys[$cloud_id] = $newkey;
		}

		return $this->_ssh_keys[$cloud_id];
	}

  public function provisionServer(\Server $server, Deployment $deployment) {
    // TODO: Currently we don't have any clouds besides AWS which use SSH keys.  When we do, some
    // refactoring will need to be done here to support that cloud.
    $result = array();
    $st = null;
    $cloud_id = $server->cloud_id->getVal();
    // TODO: Fix the import then find goodies here.
    // Search for the desired ServerTemplate by name and revision
    foreach( $this->_server_templates as $api_st ) {
      if (strtolower(trim($api_st->name)) == strtolower(trim($server->server_template->nickname->getVal())) &&
          $api_st->revision == $server->server_template->version->getVal()) {
        $st = $api_st;
      }
    }

    # Try to import it
    if(!$st) {
      $command = $this->client_mc->getCommand(
        'publications_import',
        array(
          'id' => $server->server_template->publication_id->getVal()
        )
      );

      $command->execute();
      $this->log->info("Imported");
      $mc_href = (string)$command->getResponse()->getHeader('Location');
      $this->log->info(sprintf("href be %s", $mc_href));      
      $st = $this->client_mc->newModel('ServerTemplate');
      $st->find_by_href($mc_href);
      $this->log->info(sprintf("The available properties are %s", print_r($st->getParameters(), true)));
      $this->_server_templates[] = $st;
    }

    if (!$st) {
      throw new \InvalidArgumentException('A server template with nickname "' . $server->server_template->nickname->getVal() . '" and version "' . $server->server_template->version->getVal() . '" was not found!');
    }
    
    if($cloud_id > RS_MAX_AWS_CLOUD_ID) {
      # Detect the right MultiCloudImage and select it, or throw an exception if there isn't a match
      $mci_href = null;
      foreach($st->multi_cloud_images() as $mci) {
        foreach($mci->settings() as $setting) {
          $cloud_link = array_filter($setting->links, function($var) {
            return $var->rel == 'cloud';
          });        
          if(array_pop($cloud_link)->href == $this->_clouds[$cloud_id]->href) {
            $mci_href = $mci->href;
          }
        }
      }
      if(!$mci_href) {
        $message = sprintf('The ServerTemplate "%s" does not have an image which supports the cloud "%s"', $st->name, $this->_clouds[$cloud_id]->name);
        throw new \InvalidArgumentException($message);
      }
    }

    $server_secgrps = array();
    foreach ( $server->security_groups as $secgrp ) {
      // This is as good as checking Cloud::supportsSecurityGroups because $this->_security_groups only
      // contains security groups for clouds where they're supported, and have already been created in the
      // ProvisioningHelper::provisionSecurityGroup method
      if (array_key_exists( $secgrp->id, $this->_security_groups )) {
        $server_secgrps[] = $this->_security_groups[$secgrp->id]['api']->href;
      }
    }

    $this->log->info("About to provision " . $server->count->getVal() . " servers of type " . $server->nickname->getVal());

    for ($i=1; $i <= $server->count->getVal(); $i++) {
      $nickname = $server->nickname->getVal();
      if($server->count->getVal() > 1) {
        $nickname .= $i;
      }

      $params = array();

      $api_server = null;
      if($cloud_id > RS_MAX_AWS_CLOUD_ID) {
        $api_server = $this->client_mc->newModel('Server');

        $params['server[name]'] = $nickname;
        $params['server[deployment_href]'] = '/api/deployments/' . $deployment->id;
        $params['server[instance][server_template_href]'] = '/api/server_templates/' . $st->id;
        $params['server[instance][cloud_href]'] = $this->_clouds[$cloud_id]->href;
        if(count($server_secgrps) > 0) {
          $params['server[instance][security_group_hrefs]'] = $server_secgrps;
        }
        $params['server[instance][multi_cloud_image_href]'] = $mci_href;        
      } else {
        $ssh_key = $this->_getSshKey( $cloud_id, $deployment->nickname, $result);

        $api_server = $this->client_ec2->newModel('Server');
        $api_server->nickname = $nickname;
        $api_server->ec2_ssh_key_href = $ssh_key->href;
        $api_server->ec2_security_groups_href = $server_secgrps;
        $api_server->server_template_href = 'https://my.rightscale.com/api/server_templates/' . $st->id;
        $api_server->deployment_href = $deployment->href;
        $api_server->instance_type = $server->instance_type->getVal();
      }
      $api_server->cloud_id = $cloud_id;
      $api_server->create($params);
      $api_server->addTags($this->_tags);

      $result[] = $api_server;
      $this->_servers[] = $api_server;

      $this->log->info(sprintf("Created Server - Name: %s ID: %s", $server->nickname->getVal(), $api_server->id), $server);
    }
    return $result;
  }

  /**
   * Creates a new deployment, using RightScale API 1.0
   *
   * @param $params An array of parameters to pass to the call.
   * @link http://reference.rightscale.com/api1.0/ApiR1V0/Docs/ApiDeployments.html#create
   * @return \RGeyer\Guzzle\Rs\Model\Ec2\Deployment
   */
  public function provisionDeployment($params) {
    $deployment = $this->client_ec2->newModel('Deployment');
    $deployment->create($params);

    $command = $this->client_ec2->getCommand('tags_set',
      array(
        'resource_href' => $deployment->href,
        'tags' => $this->_tags
      )
    );
    $command->execute();
    $command->getResult();
    return $deployment;
  }

  /**
   * Creates a new security group, using RightScale API 1.0 or 1.5 as appropriate.
   *
   * @param \SecurityGroup $security_group The Doctrine model describing the desired security group
   * @return bool|\RGeyer\Guzzle\Rs\Model\AbstractSecurityGroup False if no group was created, a created security group if successful
   */
  public function provisionSecurityGroup(\SecurityGroup $security_group) {
    $cloud_id = $security_group->cloud_id->getVal();
    // Check if this cloud supports security groups first!
    if($cloud_id > RS_MAX_AWS_CLOUD_ID &&
       !$this->_clouds[$cloud_id]->supportsSecurityGroups()) {
      $this->log->debug('The specified cloud (' . $cloud_id . ') does not support security groups, skipping the creation of the security group');
      return false;
    }

    $secGrp = null;
    if($cloud_id > RS_MAX_AWS_CLOUD_ID) {
      $secGrp = $this->client_mc->newModel('SecurityGroup');
      $secGrp->name = $security_group->name->getVal();
      $secGrp->description = $security_group->description->getVal();
    } else {
      $secGrp = $this->client_ec2->newModel('SecurityGroup');
      $secGrp->aws_group_name = $security_group->name->getVal();
      $secGrp->aws_description = $security_group->description->getVal();
    }
    $secGrp->cloud_id = $cloud_id;
    $secGrp->create();
    // This feels a bit hacky?
    $secGrp->find_by_id ( $secGrp->id );

    $this->_security_groups[$security_group->id] = array(
      'api' => $secGrp,
      'model' => $security_group // TODO: Probably don't need to store the model?
    );

    $this->log->info(sprintf("Created Security Group - Name: %s ID: %s", $security_group->name->getVal(), $secGrp->id));

    return $secGrp;
  }

  /**
   * Adds the specified rules to a security group.  This is separate from the creation of the
   * security group since groups may reference each other, meaning all groups must be created
   * first, then rules can be added later.
   *
   * @param \SecurityGroup $security_group
   * @return bool
   */
  public function provisionSecurityGroupRules(\SecurityGroup $security_group) {
    $cloud_id = $security_group->cloud_id->getVal();
    // Check if this cloud supports security groups first!
    if($cloud_id > RS_MAX_AWS_CLOUD_ID &&
       !$this->_clouds[$cloud_id]->supportsSecurityGroups()) {
      $this->log->debug('The specified cloud (' . $cloud_id . ') does not support security groups, skipping the creation of the security group rules');
      return false;
    }

    // Make sure this security group has been provisioned through the API, and that we have
    // the RightScale Guzzle Client Model cached, so we can add rules to it.
    if(!array_key_exists($security_group->id, $this->_security_groups)) {
      $this->log->warn(sprintf("No concrete security group was provisioned for security group doctrine model ID %s.  Can not create rules for a security group which does not exist!"));
      $this->log->debug(sprintf("The security group doctrine model IDs which have been provisioned by this ProvisioningHelper are (%s)", join(',', array_keys($this->_security_groups))));
      return false;
    }

    // Make sure that we can derive the owner
    if($cloud_id > RS_MAX_AWS_CLOUD_ID && !array_key_exists($cloud_id, $this->_owners)) {
      $this->log->warn(sprintf("Could not determine the 'owner' for cloud id %s.", $cloud_id));
      $this->log->debug(sprintf("The available clouds (and owners) is as follows.  %s", print_r($this->_owners, true)));
      return false;
    }

    $api = $this->_security_groups[$security_group->id]['api'];
    $owner = $cloud_id > RS_MAX_AWS_CLOUD_ID ? $this->_owners[$cloud_id] : $security_group->aws_owner;

    $this->log->info("About to provision " . count($security_group->rules) . " rules for Security Group " . $security_group->name->getVal());
    foreach ( $security_group->rules as $rule ) {
      $other_model = null; // Declare this up here so it can be accessed when logging at the end
      if($rule->ingress_group) {
        if(array_key_exists($rule->ingress_group->id, $this->_security_groups)) {
          $ingress_group = $this->_security_groups[$rule->ingress_group->id];

          $other_model = $ingress_group['model'];

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

  public function launchServers() {
    foreach($this->_servers as $server) {
      $server->launch();
    }
  }

}
