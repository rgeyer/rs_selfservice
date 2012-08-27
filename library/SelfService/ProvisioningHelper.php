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
use RGeyer\Guzzle\Rs\Model\SecurityGroup;
use RGeyer\Guzzle\Rs\Model\SecurityGroup1_5;

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
   * array(
   *  '12345' => array(
   *    'api' => \RGeyer\Guzzle\Rs\Model\SecurityGroup|SecurityGroup1_5,
   *    'model' => \SecurityGroup # The doctrine ORM model
   * );
   *
   * @var array An associative array (hash) of security groups.
   */
  protected $_security_groups = array();

  protected $_clouds;

  public function __construct($rs_account, $rs_email, $rs_password, $log) {
    $this->_now_ts = time();
    $this->log = $log;
    ClientFactory::setCredentials($rs_account, $rs_email, $rs_password);
    $this->client_ec2 = ClientFactory::getClient();
    $this->client_mc = ClientFactory::getClient('1.5');

    $_clouds = $this->client_ec2->newModel('Cloud')->indexAsHash();
  }

  public function provisionServer(Server $server) {

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
   * @return bool|\RGeyer\Guzzle\Rs\Model\SecurityGroup|\RGeyer\Guzzle\Rs\Model\SecurityGroup1_5 False if no group was created, a created security group if successful
   */
  public function provisionSecurityGroup(\SecurityGroup $security_group) {
    // Check if this cloud supports security groups first!
    if($security_group->cloud_id->getVal() > RS_MAX_AWS_CLOUD_ID &&
       !$this->_clouds[$security_group->cloud_id->getVal()]->supportsSecurityGroups()) {
      $this->log->debug('The specified cloud (' . $security_group->cloud_id->getVal() . ') does not support security groups, skipping');
      return false;
    }

    if($security_group->cloud_id->getVal() > RS_MAX_AWS_CLOUD_ID) {
      $secGrp = new SecurityGroup1_5();
      $secGrp->name = $security_group->name->getVal();
      $secGrp->description = $security_group->description->getVal();
      $secGrp->cloud_id = $security_group->cloud_id->getVal();
    } else {
      $secGrp = new SecurityGroup();
      $secGrp->aws_group_name = $security_group->name->getVal();
      $secGrp->aws_description = $security_group->description->getVal();
      $secGrp->cloud_id = $security_group->cloud_id->getVal();
    }
    $secGrp->create();
    // This feels a bit hacky?
    $secGrp->find_by_id ( $secGrp->id );

    $this->_security_groups[$secGrp->id] = array(
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
   * This currently only supports API 1.0
   *
   * TODO: Update this to support API 1.5
   *
   * @param \RGeyer\Guzzle\Rs\Model\SecurityGroup $security_group
   * @param $api_id The RightScale API ID of the (already created) security group
   * @param $aws_owner The AWS owner of the security group.
   * @return void
   */
  public function provisionSecurityGroupRule(\SecurityGroup $security_group, $api_id, $aws_owner = null) {
    $this->log->info("About to provision " . count($security_group->rules) . " rules for Security Group " . $security_group->name->getVal());
    foreach ( $security_group->rules as $rule ) {
      $params = array(
        'id' => $api_id,
        'ec2_security_group[protocol]' => $rule->ingress_protocol->getVal(),
        'ec2_security_group[from_port]' => $rule->ingress_from_port->getVal(),
        'ec2_security_group[to_port]' => $rule->ingress_to_port->getVal()
      );
      if ($rule->ingress_group) {
        $params = array_merge($params,
          array(
            'ec2_security_group[owner]' => $aws_owner,
            'ec2_security_group[group]' => sprintf ( "rsss-%s-%s", $rule->ingress_group->getVal(), $this->_now_ts)
          )
        );
      } else {
        $params = array_merge($params,
          array('ec2_security_group[cidr_ips]' => $rule->ingress_cidr_ips->getVal())
        );
      }

      $command = $this->client_ec2->getCommand( 'ec2_security_groups_update', $params );
      $command->execute();
      $result = $command->getResult();
      $this->log->info(
        sprintf("Created Security Group Rule - Group Name: %s Rule: protocol: %s ports: %s..%s %s: %s",
          $security_group->name->getVal(),
          $rule->ingress_protocol->getVal(),
          $rule->ingress_from_port->getVal(),
          $rule->ingress_to_port->getVal(),
          $rule->ingress_group ? 'group' : 'IPs',
          $rule->ingress_group ? $rule->ingress_group->getVal() : $rule->ingress_cidr_ips->getVal(),
          $api_id)
      );
    }
  }

}
