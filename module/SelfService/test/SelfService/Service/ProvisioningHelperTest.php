<?php

namespace SelfServiceTest\Service;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use RGeyer\Guzzle\Rs\Common\ClientFactory;
use SelfService\Service\ProvisioningHelper;

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
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPIClient', ClientFactory::getClient("1.5"));
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', new \Zend\Cache\Storage\Adapter\Memory());
  }

  public function testCanProvisionDeployment() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/response',
        '1.5/server_templates/json/response',
        '1.5/deployments_create/response',
        '1.5/tags_multi_add/response'
      )
    );
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $helper->setTags(array('foo', 'bar', 'baz'));
    $deployment = $helper->provisionDeployment('name', 'description');
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertContains('deployment[description]=description', strval($requests[2]));
    $this->assertContains('deployment[name]=name', strval($requests[2]));
    $this->assertContains('resource_hrefs[]=%2Fapi%2Fdeployments%2F12345&tags[]=foo&tags[]=bar&tags[]=baz', strval($requests[3]));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\Deployment', $deployment);
  }

  public function testProvisionDeploymentSetsServerTagScopeWhenSpecified() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/response',
        '1.5/server_templates/json/response',
        '1.5/deployments_create/response',
        '1.5/tags_multi_add/response'
      )
    );
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $helper->setTags(array('foo', 'bar', 'baz'));
    $deployment = $helper->provisionDeployment('name', 'description', array(), 'account');
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertContains('deployment[description]=description', strval($requests[2]));
    $this->assertContains('deployment[name]=name', strval($requests[2]));
    $this->assertContains('deployment[server_tag_scope]=account', strval($requests[2]));
    $this->assertContains('resource_hrefs[]=%2Fapi%2Fdeployments%2F12345&tags[]=foo&tags[]=bar&tags[]=baz', strval($requests[3]));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\Deployment', $deployment);
  }

  public function testProvisionDeploymentSetsServerTagScopeToDeploymentWhenNotDefined() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/response',
        '1.5/server_templates/json/response',
        '1.5/deployments_create/response',
        '1.5/tags_multi_add/response'
      )
    );
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $helper->setTags(array('foo', 'bar', 'baz'));
    $deployment = $helper->provisionDeployment('name', 'description');
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertContains('deployment[description]=description', strval($requests[2]));
    $this->assertContains('deployment[name]=name', strval($requests[2]));
    $this->assertContains('deployment[server_tag_scope]=deployment', strval($requests[2]));
    $this->assertContains('resource_hrefs[]=%2Fapi%2Fdeployments%2F12345&tags[]=foo&tags[]=bar&tags[]=baz', strval($requests[3]));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\Deployment', $deployment);
  }

  public function testCanUpdateDeploymentWithInputs() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/clouds/json/response',
        '1.5/server_templates/json/response',
        '1.5/deployments_create/response',
        '1.5/tags_multi_add/response',
        '1.5/inputs_multi_update/response'
      )
    );
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $helper->setTags(array('foo', 'bar', 'baz'));
    $inputs = array('foo/bar/baz' => 'text:foobarbaz');
    $deployment = $helper->provisionDeployment('name', 'description', $inputs);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(5, count($requests));
    $this->assertContains('inputs[foo/bar/baz]=text%3Afoobarbaz', strval($requests[4]));
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/11111";
    $security_group->description = "desc";
    $security_group->name = 'foo';
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/44444";
    $security_group->description = "desc";
    $security_group->name = 'foo';
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
      $this->getApplicationServiceLocator(),
      $log,
      array(11111 => 'owner')
    );
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/11111";
    $security_group->description = "desc";
    $security_group->name = 'foo';
    $security_group->id = 1;
    $security_group_rule = new \stdClass();
    $security_group_rule->cidr_ips = "0.0.0.0/0";
    $security_group_rule->protocol_details = new \stdClass();
    $security_group_rule->protocol_details->end_port = 22;
    $security_group_rule->protocol_details->start_port = 22;
    $security_group_rule->protocol = "tcp";
    $security_group_rule->source_type = "cidr_ips";
    $security_group->security_group_rules = array($security_group_rule);
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
      $this->getApplicationServiceLocator(),
      $log,
      array(11111 => 'owner')
    );
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/11111";
    $security_group->description = "desc";
    $security_group->name = 'foo';
    $security_group->id = 1;
    $security_group_rule = new \stdClass();
    $security_group_rule->cidr_ips = "0.0.0.0/0";
    $security_group_rule->protocol_details = new \stdClass();
    $security_group_rule->protocol_details->end_port = 22;
    $security_group_rule->protocol_details->start_port = 22;
    $security_group_rule->protocol = "tcp";
    $security_group_rule->source_type = "cidr_ips";
    $security_group->security_group_rules = array($security_group_rule);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(2, count($requests));
  }

  public function testProvisionSecurityGroupRulesErrorsWhenOwnerNotKnown() {
    $log = $this->getMock('Zend\Log\Logger');
    $log->expects($this->exactly(1))
      ->method('info');
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
      $this->getApplicationServiceLocator(),
      $log,
      array()
    );
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/11111";
    $security_group->description = "desc";
    $security_group->name = 'foo';
    $security_group->id = 1;
    $security_group_rule = new \stdClass();
    $security_group_rule->cidr_ips = "0.0.0.0/0";
    $security_group_rule->protocol_details = new \stdClass();
    $security_group_rule->protocol_details->end_port = 22;
    $security_group_rule->protocol_details->start_port = 22;
    $security_group_rule->protocol = "tcp";
    $security_group_rule->source_type = "cidr_ips";
    $security_group->security_group_rules = array($security_group_rule);
    $helper->provisionSecurityGroup($security_group);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(4, count($requests));
  }

  public function testProvisionSecurityGroupRulesWarnsWhenIngressGroupNotCreated() {
    # ProvisioningHelper line 343
    $log = $this->getMock('Zend\Log\Logger');
    $log->expects($this->exactly(2))
      ->method('info');
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
      $this->getApplicationServiceLocator(),
      $log,
      array(11111 => 'owner')
    );
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/11111";
    $security_group->description = "desc";
    $security_group->name = 'foo';
    $security_group->id = 1;

    $ingress_group = new \stdClass();
    $ingress_group->id = 2;

    $security_group_rule = new \stdClass();
    $security_group_rule->ingress_group = $ingress_group;
    $security_group_rule->protocol_details = new \stdClass();
    $security_group_rule->protocol_details->end_port = 22;
    $security_group_rule->protocol_details->start_port = 22;
    $security_group_rule->protocol = "tcp";
    $security_group_rule->source_type = "group";
    $security_group->security_group_rules = array($security_group_rule);
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/44444";
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
      $this->getApplicationServiceLocator(),
      $log,
      array(11111 => 'owner')
    );
    $security_group = new \stdClass();
    $security_group->cloud_href = "/api/clouds/11111";
    $security_group->description = "desc";
    $security_group->name = "foo";
    $security_group->id = 1;

    $ingress_group = new \stdClass();
    $ingress_group->cloud_href = "/api/clouds/11111";
    $ingress_group->description = "desc";
    $ingress_group->name = "ingress";
    $ingress_group->id = 2;

    $security_group_rule = new \stdClass();
    $security_group_rule->ingress_group = $ingress_group;
    $security_group_rule->protocol_details = new \stdClass();
    $security_group_rule->protocol_details->end_port = 22;
    $security_group_rule->protocol_details->start_port = 22;
    $security_group_rule->protocol = "tcp";
    $security_group_rule->source_type = "group";
    $security_group->security_group_rules = array($security_group_rule);

    $helper->provisionSecurityGroup($security_group);
    $helper->provisionSecurityGroup($ingress_group);
    $helper->provisionSecurityGroupRules($security_group);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($responses), count($requests));
  }

  public function testCanProvisionServer() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);

    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

  public function testProvisionServerImportsTemplateIfMissing() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/publications_import/response',
      '1.5/server_template/json/response',
      '1.5/server_templates/json/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[22222]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[22222];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "foo";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/22222";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

  public function testProvisionServerUsesInstanceTypeWhenDefaultIsAvailable() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/no_instance_type/response',
      '1.5/instance_types/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->instance_type_href = "/api/clouds/11111/instance_types/12345";
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;

    $cache_adapter = $this->getApplicationServiceLocator()->get('cache_storage_adapter');
    $this->assertFalse($cache_adapter->hasItem('instance_types_11111'));

    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains('instance_type_href]=%2Fapi%2Fclouds%2F11111%2Finstance_types%2F12345',strval($responses[8]));}

  public function testProvisionServerPicksInstanceTypeWhenNoDefaultIsAvailable() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/no_instance_type/response',
      '1.5/instance_types/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;

    $cache_adapter = $this->getApplicationServiceLocator()->get('cache_storage_adapter');
    $this->assertFalse($cache_adapter->hasItem('instance_types_11111'));

    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains('instance_type_href]=%2Fapi%2Fclouds%2F12345%2Finstance_types%2F12345OPP9B9RLTDK',strval($responses[8]));
  }

  public function testProvisionServerUsesDatacentersWhenDefaultIsAvailable() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/no_instance_type/response',
      '1.5/instance_types/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->datacenter_hrefs = array("/api/clouds/11111/datacenters/12345");
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;

    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains('datacenter_href]=%2Fapi%2Fclouds%2F11111%2Fdatacenters%2F12345',strval($responses[7]));
  }

  public function testProvisionServerPicksDatacentersWhenNoDefaultIsAvailable() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/no_instance_type/response',
      '1.5/instance_types/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;

    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains('datacenter_href]=%2Fapi%2Fclouds%2F12345%2Fdatacenters%2F12345F8AT46B08LN',strval($responses[8]));}

  public function testCanProvisionServerWithVoteAlertSpec() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response',
      '1.5/alert_specs_create/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);

    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;
    $alert_spec_model = new \stdClass();
    $alert_spec_model->name = "foo";
    $alert_spec_model->file = "file";
    $alert_spec_model->variable = "var";
    $alert_spec_model->condition = "==";
    $alert_spec_model->threshold = "threshold";
    $alert_spec_model->duration = 1;
    $alert_spec_model->vote_tag = "tag";
    $alert_spec_model->vote_type = "grow";
    $server_model->alert_specs = array($alert_spec_model);
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("alert_spec[subject_href]=%2Fapi%2Fservers", strval($responses[9]));
    $this->assertContains("alert_spec[vote_type]=grow", strval($responses[9]));
    $this->assertContains("alert_spec[vote_tag]=tag", strval($responses[9]));
  }

  public function testCanProvisionServerWithEscalationAlertSpec() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response',
      '1.5/alert_specs_create/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);

    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;
    $alert_spec_model = new \stdClass();
    $alert_spec_model->name = "foo";
    $alert_spec_model->file = "file";
    $alert_spec_model->variable = "var";
    $alert_spec_model->condition = "==";
    $alert_spec_model->threshold = "threshold";
    $alert_spec_model->duration = 1;
    $alert_spec_model->escalation_name = "critical";
    $server_model->alert_specs = array($alert_spec_model);
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("alert_spec[subject_href]=%2Fapi%2Fservers", strval($responses[9]));
    $this->assertContains("alert_spec[escalation_name]=critical", strval($responses[9]));
  }

  public function testProvisionServerThrowsErrorIfTemplateNotImported() {
    $this->markTestSkipped("Impossible to get this error, since an ST will either be found, or imported.  If import fails, a different and more nasty error is thrown");
  }

  public function testProvisionServerThrowsErrorIfCloudNotSupportedByAnyMCI() {
    $this->markTestSkipped("Kinda untestable because there are nearly 600 results in the mock file, need mocks specific to these tests");
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
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
      '1.5/server_templates/json/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/server_arrays_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[22222]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[22222];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "foo";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/22222";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
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
      '1.5/instance_types/json/response',
      '1.5/server_arrays_create/response',
      '1.5/tags_multi_add/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains('instance_type_href]=%2Fapi%2Fclouds%2F12345%2Finstance_types%2F12345OPP9B9RLTDK',strval($responses[7]));
  }

  public function testCanProvisionServerArrayWithVoteAlertSpec() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/server_arrays_create/response',
      '1.5/tags_multi_add/response',
      '1.5/alert_specs_create/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $alert_spec_model = new \stdClass();
    $alert_spec_model->name = "foo";
    $alert_spec_model->file = "file";
    $alert_spec_model->variable = "var";
    $alert_spec_model->condition = "==";
    $alert_spec_model->threshold = "threshold";
    $alert_spec_model->duration = 1;
    $alert_spec_model->vote_tag = "tag";
    $alert_spec_model->vote_type = "grow";
    $array_model->alert_specs = array($alert_spec_model);
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("alert_spec[subject_href]=%2Fapi%2Fserver_arrays", strval($responses[8]));
    $this->assertContains("alert_spec[vote_type]=grow", strval($responses[8]));
    $this->assertContains("alert_spec[vote_tag]=tag", strval($responses[8]));
  }

  public function testCanProvisionServerArrayWithEscalationAlertSpec() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/server_arrays_create/response',
      '1.5/tags_multi_add/response',
      '1.5/alert_specs_create/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $alert_spec_model = new \stdClass();
    $alert_spec_model->name = "foo";
    $alert_spec_model->file = "file";
    $alert_spec_model->variable = "var";
    $alert_spec_model->condition = "==";
    $alert_spec_model->threshold = "threshold";
    $alert_spec_model->duration = 1;
    $alert_spec_model->escalation_name = "critical";
    $array_model->alert_specs = array($alert_spec_model);
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("alert_spec[subject_href]=%2Fapi%2Fserver_arrays", strval($responses[8]));
    $this->assertContains("alert_spec[escalation_name]=critical", strval($responses[8]));
  }

  public function testProvisionServerArrayUsesDatacentersWhenDefaultIsAvailable() {
    $this->markTestSkipped("See: https://github.com/rgeyer/rs_guzzle_client/issues/6");
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->datacenter_hrefs = array("/api/clouds/11111/datacenters/11111");
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("datacenter_href]=%2Fapi%2Fclouds%2Fdatacenters%2F/11111", strval($responses[6]));
  }

  public function testProvisionServerArraySetsDecisionThresholdIfMissing() {
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("decision_threshold]=51", strval($responses[6]));
  }

  public function testProvisionServerArrayUsesDecisionThresholdIfSet() {
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "22";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("decision_threshold]=22", strval($responses[6]));
  }

  public function testProvisionServerArraySetsPacingIfMissing() {
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("resize_calm_time]=10", strval($responses[6]));
    $this->assertContains("resize_up_by]=3", strval($responses[6]));
    $this->assertContains("resize_down_by]=1", strval($responses[6]));
  }

  public function testProvisionServerArrayUsesPacingIfSet() {
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $pacing = new \stdClass();
    $pacing->resize_calm_time = 30;
    $pacing->resize_up_by = 10;
    $pacing->resize_down_by = 5;
    $elasticity_params->pacing = $pacing;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("resize_calm_time]=30", strval($responses[6]));
    $this->assertContains("resize_up_by]=10", strval($responses[6]));
    $this->assertContains("resize_down_by]=5", strval($responses[6]));
  }

  public function testProvisionServerArraySetsBoundsIfMissing() {
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("bounds][max_count]=10", strval($responses[6]));
    $this->assertContains("bounds][min_count]=2", strval($responses[6]));
  }

  public function testProvisionServerArrayUsesBoundsIfSet() {
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 100;
    $bounds->min_count = 10;
    $elasticity_params->bounds = $bounds;
    $pacing = new \stdClass();
    $pacing->resize_calm_time = 30;
    $pacing->resize_up_by = 10;
    $pacing->resize_down_by = 5;
    $elasticity_params->pacing = $pacing;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("bounds][max_count]=100", strval($responses[6]));
    $this->assertContains("bounds][min_count]=10", strval($responses[6]));
  }

  public function testProvisionServerArrayQueueSpecific() {
    $this->markTestSkipped("Too lazy to implement queue specific right now");
  }

  public function testProvisionServerArraySetsStateIfSet() {
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $array_model->state = 'enabled';
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("server_array[state]=enabled", strval($responses[6]));
  }

  public function testProvisionServerArrayUsesOptimizedIfSet() {
    $this->markTestSkipped("rs_guzzle_client doesn't support the param yet.");
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
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $array_model = new \stdClass();
    $elasticity_params = new \stdClass();
    $bounds = new \stdClass();
    $bounds->max_count = 10;
    $bounds->min_count = 2;
    $elasticity_params->bounds = $bounds;
    $alert_specific_params = new \stdClass();
    $array_model->array_type = "alert";
    $alert_specific_params->voters_tag_predicate = "tag";
    $alert_specific_params->decision_threshold = "51";
    $elasticity_params->alert_specific_params = $alert_specific_params;
    $array_model->elasticity_params = $elasticity_params;
    $array_model->name = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $array_model->instance = $instance_model;
    $array_model->optimized = 'true';
    $provisioned_stuff = $helper->provisionServerArray($array_model, $deployment);
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $responses = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(count($request_paths), count($responses));
    $this->assertContains("server_array[state]=enabled", strval($responses[6]));}

  public function testProvisionServerArrayUsesScheduleIfSet() {
    $this->markTestSkipped("rs_guzzle_client doesn't support the param yet.");
  }

  public function testProvisionServerArrayThrowsErrorIfTemplateNotImported() {
    $this->markTestSkipped("Impossible to get this error, since an ST will either be found, or imported.  If import fails, a different and more nasty error is thrown");
  }

  public function testProvisionServerArrayThrowsErrorIfCloudNotSupportedByAnyMCI() {
    $this->markTestSkipped("Kinda untestable becase there are nearly 600 results in the mock file, need mocks specific to these tests");
  }

  public function testCanLaunchAllServers() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response',
      '1.5/servers_launch/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 1;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    $helper->launchServers();
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(1, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

  public function testCanLaunchAllServersWhenThereAreMultiplesOfThatServer() {
    $log = $this->getMock('Zend\Log\Logger');
    $request_paths = array(
      '1.5/clouds/json/with_different_ids/response',
      '1.5/server_templates/json/response',
      '1.5/deployments_create/response',
      '1.5/tags_multi_add/response',
      '1.5/multi_cloud_images/json/response',
      '1.5/multi_cloud_image_settings/json/response',
      '1.5/datacenters/json/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response',
      '1.5/servers_create/response',
      '1.5/tags_multi_add/response',
      '1.5/servers_launch/response',
      '1.5/servers_launch/response',
      '1.5/servers_launch/response'
    );
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),$request_paths);
    $helper = new ProvisioningHelper($this->getApplicationServiceLocator(), $log, array());
    $clouds = $helper->getClouds();
    # TODO: Hacky, hacky, hacky...
    $clouds[11111]->href = '/api/clouds/12345';
    $clouds[12345] = $clouds[11111];
    $helper->setClouds($clouds);
    $deployment = $helper->provisionDeployment('foo');
    $server_template_model = new \stdClass();
    $server_template_model->name = "Database Manager for Microsoft SQL Server (v12.11.1-LTS)";
    $server_template_model->publication_id = "1234";
    $server_template_model->revision = 5;
    $server_model = new \stdClass();
    $server_model->count = 3;
    $server_model->name_prefix = "DB";
    $instance_model = new \stdClass();
    $instance_model->cloud_href = "/api/clouds/11111";
    $instance_model->server_template = $server_template_model;
    $instance_model->security_groups = array();
    $server_model->instance = $instance_model;
    $provisioned_stuff = $helper->provisionServer($server_model, $deployment);
    $helper->launchServers();
    # Cloud 11111 does not support security groups, so only one item (a server) is provisioned
    $this->assertEquals(3, count($provisioned_stuff));
    # Make sure the helper made all of the expected API calls
    $this->assertEquals(count($request_paths), count($this->_guzzletestcase->getMockedRequests()));
  }

}