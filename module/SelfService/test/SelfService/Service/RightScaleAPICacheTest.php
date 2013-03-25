<?php

namespace SelfServiceTest\Service;

use Guzzle\Tests\GuzzleTestCase;
use RGeyer\Guzzle\Rs\Common\ClientFactory;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class RightScaleAPICacheTest extends AbstractHttpControllerTestCase {

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
  }

  public function testFetchesCloudsFromApiWhenNotCached() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),array('1.5/clouds/json/response'));
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $this->assertFalse($standin_adapter->hasItem('clouds'), "Cache Storage Adapter already had clouds item");

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');
    $clouds = $cached_client->getClouds();

    $this->assertTrue($standin_adapter->hasItem('clouds'), "Cache Storage Adapter did not have clouds item");
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));

    $clouds = $cached_client->getClouds();
    $this->assertEquals(count($clouds), count($cached_client->getClouds()));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\Cloud', $clouds[0]);
    $this->assertEquals(6, count($clouds[0]->getParameters()));
  }

  public function testFetchesInstanceTypesFromApiWhenNotCached() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/instance_types/json/response',
        '1.5/instance_types/json/response'
      )
    );
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $this->assertFalse($standin_adapter->hasItem('instance_types_1'), "Cache Storage Adapter already had instance_types item");

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');
    $instance_types = $cached_client->getInstanceTypes(1);

    $this->assertTrue($standin_adapter->hasItem('instance_types_1'), "Cache Storage Adapter did not have instance_types item");
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));
    $this->assertEquals(count($instance_types), count($cached_client->getInstanceTypes(1)));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\InstanceType', $instance_types[0]);

    $instance_types = $cached_client->getInstanceTypes(2);

    $this->assertTrue($standin_adapter->hasItem('instance_types_2'), "Cache Storage Adapter did not have instance_types item");
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(2, count($requests));
    $this->assertEquals(count($instance_types), count($cached_client->getInstanceTypes(2)));
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\InstanceType', $instance_types[0]);
    $this->assertEquals(13, count($instance_types[0]->getParameters()));
  }
}