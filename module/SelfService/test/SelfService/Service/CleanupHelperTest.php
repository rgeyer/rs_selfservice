<?php

namespace SelfServiceTest\Service;

use Guzzle\Tests\GuzzleTestCase;

use SelfService\Service\CleanupHelper;
use RGeyer\Guzzle\Rs\Common\ClientFactory;
use RGeyer\Guzzle\Rs\Model\Mc\ServerArray;
use RGeyer\Guzzle\Rs\Model\Mc\Server;
use RGeyer\Guzzle\Rs\Model\Mc\SecurityGroup;
use RGeyer\Guzzle\Rs\Model\Mc\SshKey;
use RGeyer\Guzzle\Rs\Model\Mc\Deployment;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;


use SelfService\Entity\ProvisionedArray;
use SelfService\Entity\ProvisionedServer;
use SelfService\Entity\ProvisionedSshKey;
use SelfService\Entity\ProvisionedDeployment;
use SelfService\Entity\ProvisionedSecurityGroup;

class CleanupHelperTest extends AbstractHttpControllerTestCase {

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

  public function testCanCleanupServerArrayWithNoInstances() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/server_array/json/response',
        '1.5/server_arrays_destroy/response'
      )
    );
    $api_model = new ServerArray();
    $api_model->href = "/api/server_arrays/1234";
    $orm_model = new ProvisionedArray($api_model);
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $response = $helper->cleanupServerArray($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertTrue($response);
    $this->assertEquals(2, count($requests));
    foreach($requests as $request) {
      $this->assertNotContains('multi_terminate', strval($request));
    }
  }

  public function testCleanupServerArrayTerminatesRunningInstances() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/server_array/json/with_instances/response',
        '1.5/server_arrays_multi_terminate/response',
        # TODO: What happens when termination is requested when they're already terminating?
        '1.5/server_array/json/response',
        '1.5/server_arrays_destroy/response'
      )
    );
    $api_model = new ServerArray();
    $api_model->href = "/api/server_arrays/1234";
    $orm_model = new ProvisionedArray($api_model);
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);

    # Try once, there will be running instances, and a terminate request will be made
    $response = $helper->cleanupServerArray($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertFalse($response);
    $this->assertEquals(2, count($requests));
    $this->assertContains('multi_terminate', strval($requests[1]));

    # next attempt covered by the first test that successfully destroys when no instances are
    # found running.
  }

  public function testCanCleanupInactiveServer() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/server/json/state/inactive/response',
        '1.5/servers_destroy/response'
      )
    );
    $api_model = new Server();
    $api_model->href = "/api/servers/1234";
    $orm_model = new ProvisionedServer($api_model);
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $response = $helper->cleanupServer($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertTrue($response);
    $this->assertEquals(2, count($requests));
    foreach($requests as $request) {
      $this->assertNotContains('terminate', strval($request));
    }
    $this->assertContains('DELETE /api/servers/12345', strval($requests[1]));
  }

  public function testCleanupServerTerminatesWhenInTerminableState() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/server/json/state/operational/response',
        '1.5/servers_terminate/response'
      )
    );
    $api_model = new Server();
    $api_model->href = "/api/servers/1234";
    $orm_model = new ProvisionedServer($api_model);
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $response = $helper->cleanupServer($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertFalse($response);
    $this->assertEquals(2, count($requests));
    foreach($requests as $request) {
      $this->assertNotContains('DELETE /api/servers/12345', strval($request));
    }
    $this->assertContains('terminate', strval($requests[1]));
  }

  public function testCleanupServerTakesNoActionWhenDecommissioning() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/server/json/state/decommissioning/response'
      )
    );
    $api_model = new Server();
    $api_model->href = "/api/servers/1234";
    $orm_model = new ProvisionedServer($api_model);
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $response = $helper->cleanupServer($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertFalse($response);
    $this->assertEquals(1, count($requests));
  }

  public function testCanCleanupDeployment() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/deployment/json/response',
        '1.5/deployments_destroy/response'
      )
    );
    $api_model = new Deployment();
    $api_model->href = "/api/deployments/1234";
    $orm_model = new ProvisionedDeployment($api_model);
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $response = $helper->cleanupDeployment($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertTrue($response);
    $this->assertEquals(2, count($requests));
    $this->assertContains('DELETE /api/deployments/12345', strval($requests[1]));
  }

  public function testCanCleanupSshKey() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/ssh_key/json/response',
        '1.5/ssh_keys_destroy/response'
      )
    );
    $api_model = new SshKey();
    $api_model->href = "/api/cloud/12345/ssh_keys/1234";
    $orm_model = new ProvisionedSshKey($api_model);
    $orm_model->cloud_id = '12345';
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $response = $helper->cleanupSshKey($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertTrue($response);
    $this->assertEquals(2, count($requests));
    $this->assertContains('DELETE /api/clouds/12345/ssh_keys/', strval($requests[1]));
  }

  public function testCanCleanupSecurityGroupRules() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/security_group/json/response',
        '1.5/security_group_rules/json/response',
        '1.5/security_group_rules_destroy/response',
        '1.5/security_group_rules_destroy/response'
      )
    );
    $api_model = new SecurityGroup();
    $api_model->href = "/api/clouds/12345/security_groups/ABC123";
    $orm_model = new ProvisionedSecurityGroup($api_model);
    $orm_model->cloud_id = '12345';
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $helper->cleanupSecurityGroupRules($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(4, count($requests));
    $this->assertContains('DELETE /api/clouds/12345/security_groups/ABC123/security_group_rules/12345', strval($requests[2]));
    $this->assertContains('DELETE /api/clouds/12345/security_groups/ABC123/security_group_rules/12345', strval($requests[3]));
  }

  public function testCanCleanupSecurityGroup() {
    $log = $this->getMock('Zend\Log\Logger');
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/security_group/json/response',
        '1.5/security_groups_destroy/response'
      )
    );
    $api_model = new SecurityGroup();
    $api_model->href = "/api/clouds/12345/security_groups/ABC123";
    $orm_model = new ProvisionedSecurityGroup($api_model);
    $orm_model->cloud_id = '12345';
    $helper = new CleanupHelper('123', 'foo@bar.baz', 'password', $log);
    $helper->cleanupSecurityGroup($orm_model);
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(2, count($requests));
    $this->assertContains('DELETE /api/clouds/12345/security_groups/', strval($requests[1]));
  }
}