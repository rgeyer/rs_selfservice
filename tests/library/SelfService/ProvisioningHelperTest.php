<?php

use Guzzle\Tests\GuzzleTestCase;

use SelfService\ProvisioningHelper;
use RGeyer\Guzzle\Rs\Common\ClientFactory;

class ProvisioningHelperTest extends GuzzleTestCase {

  public function setUp() {
    $this->setMockBasePath(__DIR__.'/../../mock');
    $this->setMockResponse(ClientFactory::getClient("1.5"), '1.5/login');
		ClientFactory::getClient("1.5")->post('/api/session')->send();
  }

  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
  }

  public function testCanProvisionDeployment() {
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/response',
        '1.5/server_templates/json/response',
        '1.5/deployments_create/response',
        '1.5/tags_multi_add/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper('123', 'foo@bar.baz', 'password', new stdClass(), array());
    $helper->setTags(array('foo', 'bar', 'baz'));
    $deployment = $helper->provisionDeployment(array('deployment[description]' => 'description', 'deployment[name]' => 'name'));
    $requests = $this->getMockedRequests();
    $this->assertContains('deployment%5Bdescription%5D=description&deployment%5Bname%5D=name', strval($requests[2]));
    $this->assertContains('resource_hrefs%5B%5D=%2Fapi%2Fdeployments%2F12345&tags%5B%5D%5B0%5D=foo&tags%5B%5D%5B1%5D=bar&tags%5B%5D%5B2%5D=baz', strval($requests[3]));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\Deployment', $deployment);
  }

  public function testCanProvisionSecurityGroup() {
    $log = $this->getMock('Zend_Log');
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(11111);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');;
    $helper->provisionSecurityGroup($security_group);
    $requests = $this->getMockedRequests();
    $this->assertEquals(4, count($requests));
  }

  public function testProvisionSecurityGroupBailsIfNotSupportedByCloud() {
    $log = $this->getMock('Zend_Log');
    $log->expects($this->once())
      ->method('__call')
      ->with('debug', array('The specified cloud (44444) does not support security groups, skipping the creation of the security group'));
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(44444);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');
    $helper->provisionSecurityGroup($security_group);
  }

  public function testCanProvisionSecurityGroupRules() {
    $log = $this->getMock('Zend_Log');
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response',
        '1.5/security_group_rules_create/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper(
      '123',
      'foo@bar.baz',
      'password',
      $log,
      array(11111 => 'owner')
    );
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(11111);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');
    $security_group->id = 1;
    $security_group_rule = new SecurityGroupRule();
    $security_group_rule->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
    $security_group_rule->ingress_from_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_to_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_protocol = new TextProductMetaInput("tcp");
    $security_group->rules = array($security_group_rule);
    $helper->provisionSecurityGroup($security_group);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->getMockedRequests();
    $this->assertEquals(5, count($requests));
  }

  public function testProvisionSecurityGroupRulesErrorsWhenSecurityGroupNotCreated() {
    $log = $this->getMock('Zend_Log');
    $log->expects($this->exactly(2))
      ->method('__call')
      ->with($this->logicalOr
        (
          'warn', array("No concrete security group was provisioned for security group doctrine model ID 1.  Can not create rules for a security group which does not exist!"),
          'debug', array("The security group doctrine model IDs which have been provisioned by this ProvisioningHelper are ()")
        )
      );
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper(
      '123',
      'foo@bar.baz',
      'password',
      $log,
      array(11111 => 'owner')
    );
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(11111);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');
    $security_group->id = 1;
    $security_group_rule = new SecurityGroupRule();
    $security_group_rule->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
    $security_group_rule->ingress_from_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_to_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_protocol = new TextProductMetaInput("tcp");
    $security_group->rules = array($security_group_rule);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->getMockedRequests();
    $this->assertEquals(2, count($requests));
  }

  public function testProvisionSecurityGroupRulesErrorsWhenOwnerNotKnown() {
    $log = $this->getMock('Zend_Log');
    $log->expects($this->exactly(3))
      ->method('__call')
      ->with($this->logicalOr
        (
          'info', array("Created Security Group - Name: foo ID: 1"),
          'warn', array("Could not determine the 'owner' for cloud id 11111."),
          'debug', array("The available clouds (and owners) is as follows.  ")
        )
      );
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper(
      '123',
      'foo@bar.baz',
      'password',
      $log,
      array()
    );
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(11111);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');
    $security_group->id = 1;
    $security_group_rule = new SecurityGroupRule();
    $security_group_rule->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
    $security_group_rule->ingress_from_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_to_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_protocol = new TextProductMetaInput("tcp");
    $security_group->rules = array($security_group_rule);
    $helper->provisionSecurityGroup($security_group);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->getMockedRequests();
    $this->assertEquals(4, count($requests));
  }

  public function testProvisionSecurityGroupRulesWarnsWhenIngressGroupNotCreated() {
    # ProvisioningHelper line 343
    $log = $this->getMock('Zend_Log');
    $log->expects($this->exactly(3))
      ->method('__call')
      ->with($this->logicalOr
        (
          'info', array("Created Security Group - Name: foo ID: 1"),
          'info', array("About to provision 1 rules for Security Group foo"),
          'warn', array("No concrete security group was provisioned for security group doctrine model ID 2.  Skipping rule creation")
        )
      );
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper(
      '123',
      'foo@bar.baz',
      'password',
      $log,
      array(11111 => 'owner')
    );
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(11111);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');
    $security_group->id = 1;

    $ingress_group = new SecurityGroup();
    $ingress_group->id = 2;

    $security_group_rule = new SecurityGroupRule();
    $security_group_rule->ingress_group = $ingress_group;
    $security_group_rule->ingress_from_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_to_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_protocol = new TextProductMetaInput("tcp");
    $security_group->rules = array($security_group_rule);
    $helper->provisionSecurityGroup($security_group);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->getMockedRequests();
    $this->assertEquals(4, count($requests));
  }

  public function testProvisionSecurityGroupRulesBailsIfNotSupportedByCloud() {
    $log = $this->getMock('Zend_Log');
    $log->expects($this->once())
      ->method('__call')
      ->with('debug', array('The specified cloud (44444) does not support security groups, skipping the creation of the security group rules'));
    $this->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response'
      )
    );
    $helper = new \SelfService\ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(44444);
    $helper->provisionSecurityGroupRules($security_group);
  }

  public function testCanProvisionServer() {
    $log = $this->getMock('Zend_Log');
    # TODO: This breaks if github.com/rgeyer/rs_guzzle_client mocks change, probably an improvement
    # for Guzzle 3 mocks..
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new \SelfService\ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment(array('deployment[name]' => 'foo'));
    $server_template_model = new ServerTemplate();
    $server_template_model->nickname = new TextProductMetaInput("Database Manager for Microsoft SQL Server (v12.11.1-LTS)");
    $server_template_model->publication_id = new TextProductMetaInput("1234");
    $server_template_model->version = new NumberProductMetaInput(5);
    $server_model = new Server();
    $server_model->cloud_id = new NumberProductMetaInput(11111);
    $server_model->count = new NumberProductMetaInput(1);
    $server_model->nickname = new TextProductMetaInput("DB");
    $server_model->server_template = $server_template_model;
    $server_model->security_groups = array();
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $this->assertEquals(count($request_paths), count($this->getMockedRequests()));
  }

  public function testProvisionServerImportsTemplateIfMissing() {
    $log = $this->getMock('Zend_Log');
    # TODO: This breaks if github.com/rgeyer/rs_guzzle_client mocks change, probably an improvement
    # for Guzzle 3 mocks..
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/publications_import/response',
      '1.5/server_template/json/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new \SelfService\ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[22222]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[22222];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment(array('deployment[name]' => 'foo'));
    $server_template_model = new ServerTemplate();
    $server_template_model->nickname = new TextProductMetaInput("foo");
    $server_template_model->publication_id = new TextProductMetaInput("1234");
    $server_template_model->version = new NumberProductMetaInput(5);
    $server_model = new Server();
    $server_model->cloud_id = new NumberProductMetaInput(22222);
    $server_model->count = new NumberProductMetaInput(1);
    $server_model->nickname = new TextProductMetaInput("DB");
    $server_model->server_template = $server_template_model;
    $server_model->security_groups = array();
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    $this->assertEquals(count($request_paths), count($this->getMockedRequests()));
  }

  public function testProvisionServerThrowsErrorIfTemplateNotImported() {
    $this->markTestSkipped("Impossible to get this error, since an ST will either be found, or imported.  If import fails, a different and more nasty error is thrown");
  }

  public function testProvisionServerThrowsErrorIfCloudNotSupportedByAnyMCI() {
    $this->markTestSkipped("Kinda untestable becase there are nearly 600 results in the mock file, need mocks specific to these tests");
  }

}