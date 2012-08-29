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
use RGeyer\Guzzle\Rs\Model\Deployment;

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
   * This will include an "owner" property which contains the owner account ID
   *
   * @var array An associative array (hash) of clouds as returned by RGeyer\Guzzle\Rs\Model\Cloud::indexAsHash
   */
  protected $_clouds;

  /**
   * @var RGeyer\Guzzle\Rs\Model\Ec2\ServerTemplate[] The response of the RightScale API Client 1.0 servertemplate index
   */
  protected $_server_templates;

  public function __construct($rs_account, $rs_email, $rs_password, $log, $owners = array()) {
    $this->_now_ts = time();
    $this->log = $log;
    ClientFactory::setCredentials($rs_account, $rs_email, $rs_password);
    $this->client_ec2 = ClientFactory::getClient();
    $this->client_mc = ClientFactory::getClient('1.5');

    $this->_clouds = $this->client_ec2->newModel('Cloud')->indexAsHash();
    foreach($this->_clouds as $cloud_id => $cloud) {
      if(array_key_exists($cloud_id, $owners)) {
        $this->_clouds[$cloud_id]->owner = $owners[$cloud_id];
      }
    }

    $this->_server_templates = $this->client_ec2->newModel('ServerTemplate')->index();
  }

  public function provisionServer(\Server $server) {
    $st = null;
    // Search for the desired ServerTemplate by name and revision
    foreach( $this->_server_templates as $api_st ) {
      if ($api_st->nickname == $server->server_template->nickname->getVal() &&
          $api_st->version == $server->server_template->version->getVal()) {
        $st = $api_st->href;
      }
    }

    if (!$st) {
      throw new \InvalidArgumentException('A server template with nickname "' . $server->server_template->nickname->getVal() . '" and version "' . $server->server_template->version->getVal() . '" was not found!');
    }

    $server_secgrps = array();
    foreach ( $server->security_groups as $secgrp ) {
      if (array_key_exists( $secgrp->id, $this->_security_groups )) {
        $server_secgrps[] = $this->_security_groups[$secgrp->id]['api']->href;
      }
    }

    $this->log->info("About to provision " . $server->count->getVal() . " servers of type " . $server->nickname->getVal());

    for ($i=1; $i <= $server->count->getVal(); $i++) {
      $api_server = new \RGeyer\Guzzle\Rs\Model\Server ();
      $api_server->nickname = $server->nickname->getVal();
      if($server->count->getVal() > 1) {
        $api_server->nickname .= $i;
      }
      $api_server->ec2_ssh_key_href = $this->_getSshKey($server->cloud_id->getVal(), sprintf("rsss-%s-%s", $product->name, $now), $prov_prod)->href;
      $api_server->ec2_security_groups_href = $server_secgrps;
      $api_server->server_template_href = $st;
      $api_server->deployment_href = $deployment->href;
      $api_server->cloud_id = $server->cloud_id->getVal();
      $api_server->instance_type = $server->instance_type->getVal();
      $api_server->create();
      $prov_serv = new ProvisionedServer();
      $prov_serv->href = $api_server->href;
      $prov_serv->cloud_id = $server->cloud_id->getVal();
      $prov_prod->provisioned_objects[] = $prov_serv;
      $this->log->info(sprintf("Created Server - Name: %s ID: %s", $server->nickname->getVal(), $api_server->id), $server);
      $debug_api_server = array ('api' => $api_server, 'model' => $server );
      $api_servers[$server->id][] = $debug_api_server;
    }
  }

  /**
   * Creates a new deployment, using RightScale API 1.0
   *
   * @param $params An array of parameters to pass to the call.
   * @link http://reference.rightscale.com/api1.0/ApiR1V0/Docs/ApiDeployments.html#create
   * @return \RGeyer\Guzzle\Rs\Model\Deployment
   */
  public function provisionDeployment($params) {
    $deployment = $this->client_ec2->newModel('Deployment');
    $deployment->create($params);
    return $deployment;
  }

  /**
   * Creates a new security group, using RightScale API 1.0 or 1.5 as appropriate.
   *
   * @param \SecurityGroup $security_group The Doctrine model describing the desired security group
   * @return bool|\RGeyer\Guzzle\Rs\Model\AbstractSecurityGroup False if no group was created, a created security group if successful
   */
  public function provisionSecurityGroup(\SecurityGroup $security_group) {
    // Check if this cloud supports security groups first!
    if($security_group->cloud_id->getVal() > RS_MAX_AWS_CLOUD_ID &&
       !$this->_clouds[$security_group->cloud_id->getVal()]->supportsSecurityGroups()) {
      $this->log->debug('The specified cloud (' . $security_group->cloud_id->getVal() . ') does not support security groups, skipping the creation of the security group');
      return false;
    }

    if($security_group->cloud_id->getVal() > RS_MAX_AWS_CLOUD_ID) {
      $secGrp = $this->client_mc->newModel('Mc\SecurityGroup');
      $secGrp->name = $security_group->name->getVal();
      $secGrp->description = $security_group->description->getVal();
      $secGrp->cloud_id = $security_group->cloud_id->getVal();
    } else {
      $secGrp = $this->client_ec2->newModel('Ec2\SecurityGroup');
      $secGrp->aws_group_name = $security_group->name->getVal();
      $secGrp->aws_description = $security_group->description->getVal();
      $secGrp->cloud_id = $security_group->cloud_id->getVal();
    }
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
    // Check if this cloud supports security groups first!
    if($security_group->cloud_id->getVal() > RS_MAX_AWS_CLOUD_ID &&
       !$this->_clouds[$security_group->cloud_id->getVal()]->supportsSecurityGroups()) {
      $this->log->debug('The specified cloud (' . $security_group->cloud_id->getVal() . ') does not support security groups, skipping the creation of the security group rules');
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
    if(!array_key_exists($security_group->cloud_id, $this->_clouds) ||
       !isset($this->_clouds[$security_group->cloud_id]->owner)) {
      $this->log->warn(sprintf("Could not determine the 'owner' for cloud id %s.", $security_group->cloud_id));
      $this->log->debug(sprintf("The available clouds (and owners) is as follows.  %s", print_r($this->_clouds, true)));
      return false;
    }

    $api = $this->_security_groups[$security_group->id]['api'];
    $owner = $this->_clouds[$security_group->cloud_id]->owner;

    $this->log->info("About to provision " . count($security_group->rules) . " rules for Security Group " . $security_group->name->getVal());
    foreach ( $security_group->rules as $rule ) {
      $other_model = null; // Declare this up here so it can be accessed when logging at the end
      if($rule->ingress_group) {
        if(array_key_exists($this->_security_groups, $rule->ingress_group->id)) {
          // Make sure we've got information about the ingress group
          if(!array_key_exists($rule->ingress_group->id, $this->_security_groups)) {
            $this->log->warn(sprintf("No concrete security group was provisioned for security group doctrine model ID %s.  Skipping rule creation", $rule->ingress_group->id));
            $this->log->debug(sprintf("The security group doctrine model IDs which have been provisioned by this ProvisioningHelper are (%s)", join(',', array_keys($this->_security_groups))));
            continue;
          }

          $other_model = $this->_security_groups[$rule->ingress_group->id]['model'];
          $api->createGroupRule(
            $other_model->name->getVal(),
            $owner,
            $rule->ingress_protocol->getVal(),
            $rule->ingress_from_port->getVal(),
            $rule->ingress_to_port->getVal()
          );
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

}
