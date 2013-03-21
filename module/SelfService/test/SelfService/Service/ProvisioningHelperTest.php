<?php

namespace SelfServiceTest\Service;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use RGeyer\Guzzle\Rs\Common\ClientFactory;
use SelfService\Service\ProvisioningHelper;
use SelfService\Entity\Provisionable\Server;
use SelfService\Entity\Provisionable\ServerArray;
use SelfService\Entity\Provisionable\SecurityGroup;
use SelfService\Entity\Provisionable\ServerTemplate;
use SelfService\Entity\Provisionable\SecurityGroupRule;
use SelfService\Entity\Provisionable\MetaInputs\NumberProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput;

class ProvisioningHelperTest extends AbstractHttpControllerTestCase {

  protected $_guzzletestcase;

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../config/application.config.php'
    );
    parent::setUp();
    $this->_guzzletestcase = new \SelfServiceTest\ConcreteGuzzleTestCase();
    $this->getApplicationServiceLocator();

    ClientFactory::setCredentials('123', 'foo@bar.baz', 'password');
    $this->_guzzletestcase->setMockBasePath(__DIR__.'/../../mock');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"), '1.5/login');
		ClientFactory::getClient("1.5")->post('/api/session')->send();
  }

  public function testCanProvisionDeployment() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/response',
        '1.5/server_templates/json/response',
        '1.5/deployments_create/response',
        '1.5/tags_multi_add/response'
      )
    );
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', new \stdClass(), array());
    $helper->setTags(array('foo', 'bar', 'baz'));
    $deployment = $helper->provisionDeployment(array('deployment[description]' => 'description', 'deployment[name]' => 'name'));
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertContains('deployment%5Bdescription%5D=description&deployment%5Bname%5D=name', strval($requests[2]));
    # TODO: The tags don't seem to be getting passed properly
    #$this->assertContains('resource_hrefs%5B%5D=%2Fapi%2Fdeployments%2F12345&tags%5B%5D%5B0%5D=foo&tags%5B%5D%5B1%5D=bar&tags%5B%5D%5B2%5D=baz', strval($requests[3]));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\Deployment', $deployment);
  }

  public function testCanProvisionSecurityGroup() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response'
      )
    );
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(11111);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');;
    $helper->provisionSecurityGroup($security_group);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(4, count($requests));
  }

  public function testProvisionSecurityGroupBailsIfNotSupportedByCloud() {
    $log = $this->getMock('Zend\Log\Logger');
    $log->expects($this->once())
      ->method('debug')
      ->with('The specified cloud (44444) does not support security groups, skipping the creation of the security group');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response'
      )
    );
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(44444);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput('foo');
    $helper->provisionSecurityGroup($security_group);
  }

  public function testCanProvisionSecurityGroupRules() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response',
        '1.5/security_group_rules_create/response'
      )
    );
    $helper = new ProvisioningHelper(
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
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(5, count($requests));
  }

  public function testProvisionSecurityGroupRulesErrorsWhenSecurityGroupNotCreated() {
    $log = $this->getMock('Zend\Log\Logger');
    $log->expects($this->exactly(1))
      ->method('warn')
      ->with("No concrete security group was provisioned for security group doctrine model ID 1.  Can not create rules for a security group which does not exist!");
    $log->expects($this->exactly(1))
      ->method('debug')
      ->with("The security group doctrine model IDs which have been provisioned by this ProvisioningHelper are ()");
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response'
      )
    );
    $helper = new ProvisioningHelper(
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
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(2, count($requests));
  }

  public function testProvisionSecurityGroupRulesErrorsWhenOwnerNotKnown() {
    $log = $this->getMock('Zend\Log\Logger');
    $log->expects($this->exactly(1))
      ->method('info')
      ->with("Created Security Group - Name: foo ID: 12345I8FG7HTFVJ");
    $log->expects($this->exactly(1))
      ->method('warn')
      ->with("Could not determine the 'owner' for cloud id 11111.");
    $log->expects($this->exactly(1))
      ->method('debug')
      ->with("The available clouds (and owners) is as follows.  Array\n(\n)\n");
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response'
      )
    );
    $helper = new ProvisioningHelper(
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
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(4, count($requests));
  }

  public function testProvisionSecurityGroupRulesWarnsWhenIngressGroupNotCreated() {
    # ProvisioningHelper line 343
    $log = $this->getMock('Zend\Log\Logger');
    $log->expects($this->exactly(2))
      ->method('info')
      ->with($this->logicalOr(
        "Created Security Group - Name: foo ID: 12345I8FG7HTFVJ",
        "About to provision 1 rules for Security Group foo"
      )
    );
    $log->expects($this->exactly(1))
      ->method('warn')
      ->with("No concrete security group was provisioned for security group doctrine model ID 2.  Skipping rule creation");
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response',
        '1.5/security_groups_create/response',
        '1.5/security_group/json/response'
      )
    );
    $helper = new ProvisioningHelper(
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
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(4, count($requests));
  }

  public function testProvisionSecurityGroupRulesBailsIfNotSupportedByCloud() {
    $log = $this->getMock('Zend\Log\Logger');
    $log->expects($this->once())
      ->method('debug')
      ->with('The specified cloud (44444) does not support security groups, skipping the creation of the security group rules');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/with_different_ids/response',
        '1.5/server_templates/json/response'
      )
    );
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(44444);
    $helper->provisionSecurityGroupRules($security_group);
  }

  public function testCanProvisionSecurityGroupRulesWithIngressGroup() {
    $log = $this->getMock('Zend\Log\Logger');
    $responses = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/security_groups_create/response',
      '1.5/security_group/json/response',
      '1.5/security_groups_create/response',
      '1.5/security_group/json/response',
      '1.5/security_group_rules_create/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$responses);
    $helper = new ProvisioningHelper(
      '123',
      'foo@bar.baz',
      'password',
      $log,
      array(11111 => 'owner')
    );
    $security_group = new SecurityGroup();
    $security_group->cloud_id = new NumberProductMetaInput(11111);
    $security_group->description = new TextProductMetaInput("desc");
    $security_group->name = new TextProductMetaInput("foo");
    $security_group->id = 1;

    $ingress_group = new SecurityGroup();
    $ingress_group->cloud_id = new NumberProductMetaInput(11111);
    $ingress_group->description = new TextProductMetaInput("desc");
    $ingress_group->name = new TextProductMetaInput("ingress");
    $ingress_group->id = 2;

    $security_group_rule = new SecurityGroupRule();
    $security_group_rule->ingress_group = $ingress_group;
    $security_group_rule->ingress_from_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_to_port = new NumberProductMetaInput(22);
    $security_group_rule->ingress_protocol = new TextProductMetaInput("tcp");
    $security_group->rules = array($security_group_rule);

    $helper->provisionSecurityGroup($security_group);
    $helper->provisionSecurityGroup($ingress_group);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($responses), count($requests));
  }

  public function testCanProvisionServer() {
    $log = $this->getMock('Zend\Log\Logger');
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
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
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
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

  public function testProvisionServerImportsTemplateIfMissing() {
    $log = $this->getMock('Zend\Log\Logger');
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
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
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
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

  public function testProvisionServerPicksInstanceTypeWhenNoDefaultIsAvailable() {
    $log = $this->getMock('Zend\Log\Logger');
    # TODO: This breaks if github.com/rgeyer/rs_guzzle_client mocks change, probably an improvement
    # for Guzzle 3 mocks..
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/no_instance_type/response',
      '1.5/cloud/json/response',
      '1.5/instance_types/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
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
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains('instance_type_href%5D=%2Fapi%2Fclouds%2F12345%2Finstance_types%2F12345OPP9B9RLTDK',strval($responses[8]));
  }

  public function testProvisionServerThrowsErrorIfTemplateNotImported() {
    $this->markTestSkipped("Impossible to get this error, since an ST will either be found, or imported.  If import fails, a different and more nasty error is thrown");
  }

  public function testProvisionServerThrowsErrorIfCloudNotSupportedByAnyMCI() {
    $this->markTestSkipped("Kinda untestable becase there are nearly 600 results in the mock file, need mocks specific to these tests");
  }

  public function testCanProvisionServerArray() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/server_arrays_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
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
    $array_model = new ServerArray();
    $array_model->cloud_id = new NumberProductMetaInput(11111);
    $array_model->max_count = new NumberProductMetaInput(10);
    $array_model->min_count = new NumberProductMetaInput(2);
    $array_model->type = new TextProductMetaInput("alert");
    $array_model->tag = new TextProductMetaInput("tag");
    $array_model->nickname = new TextProductMetaInput("DB");
    $array_model->server_template = $server_template_model;
    $array_model->security_groups = array();
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

  public function testProvisionServerArrayImportsTemplateIfMissing() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/publications_import/response',
      '1.5/server_template/json/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/server_arrays_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
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
    $array_model = new ServerArray();
    $array_model->cloud_id = new NumberProductMetaInput(22222);
    $array_model->max_count = new NumberProductMetaInput(10);
    $array_model->min_count = new NumberProductMetaInput(2);
    $array_model->type = new TextProductMetaInput("alert");
    $array_model->tag = new TextProductMetaInput("tag");
    $array_model->nickname = new TextProductMetaInput("DB");
    $array_model->server_template = $server_template_model;
    $array_model->security_groups = array();
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

  public function testProvisionServerArrayPicksInstanceTypeWhenNoDefaultIsAvailable() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/no_instance_type/response',
      '1.5/cloud/json/response',
      '1.5/instance_types/json/response',
      '1.5/server_arrays_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper('123', 'foo@bar.baz', 'password', $log, array());
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
    $array_model = new ServerArray();
    $array_model->cloud_id = new NumberProductMetaInput(11111);
    $array_model->max_count = new NumberProductMetaInput(10);
    $array_model->min_count = new NumberProductMetaInput(2);
    $array_model->type = new TextProductMetaInput("alert");
    $array_model->tag = new TextProductMetaInput("tag");
    $array_model->nickname = new TextProductMetaInput("DB");
    $array_model->server_template = $server_template_model;
    $array_model->security_groups = array();
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains('instance_type_href%5D=%2Fapi%2Fclouds%2F12345%2Finstance_types%2F12345OPP9B9RLTDK',strval($responses[8]));
  }

  public function testProvisionServerArrayThrowsErrorIfTemplateNotImported() {
    $this->markTestSkipped("Impossible to get this error, since an ST will either be found, or imported.  If import fails, a different and more nasty error is thrown");
  }

  public function testProvisionServerArrayThrowsErrorIfCloudNotSupportedByAnyMCI() {
    $this->markTestSkipped("Kinda untestable becase there are nearly 600 results in the mock file, need mocks specific to these tests");
  }

}