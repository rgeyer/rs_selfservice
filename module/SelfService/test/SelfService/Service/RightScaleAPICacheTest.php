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

    $this->assertEquals(count($clouds), count($cached_client->getClouds()));
    $clouds = $cached_client->getClouds();
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\Cloud', $clouds[0]);
    $this->assertEquals(6, count($clouds[0]->getParameters()));
  }

  public function testCanRemoveCloudsFromCache() {
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('clouds'), "Cache Storage Adapter already had clouds item");
    $standin_adapter->setItem('clouds', "foo");
    $this->assertTrue($standin_adapter->hasItem('clouds'), "Cache Storage Adapter did not have clouds item");
    $cached_client->removeClouds();
    $this->assertFalse($standin_adapter->hasItem('clouds'), "Cache Storage Adapter still had clouds item after invalidation/exipration");
  }

  public function testCanReplaceCloudsInCache() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),array('1.5/clouds/json/response'));
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('clouds'), "Cache Storage Adapter already had clouds item");
    $standin_adapter->setItem('clouds', array());
    $this->assertTrue($standin_adapter->hasItem('clouds'), "Cache Storage Adapter did not have clouds item");
    $this->assertEquals(array(), $cached_client->getClouds());

    $clouds = $cached_client->updateClouds();

    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));


    $this->assertEquals(count($clouds), count($cached_client->getClouds()));
    $clouds = $cached_client->getClouds();
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
    $instance_types = $cached_client->getInstanceTypes(1);
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\InstanceType', $instance_types[0]);
    $this->assertEquals(16, count($instance_types));

    $instance_types = $cached_client->getInstanceTypes(2);

    $this->assertTrue($standin_adapter->hasItem('instance_types_2'), "Cache Storage Adapter did not have instance_types item");
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(2, count($requests));
    $this->assertEquals(count($instance_types), count($cached_client->getInstanceTypes(2)));
    $instance_types = $cached_client->getInstanceTypes(2);
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\InstanceType', $instance_types[0]);
    $this->assertEquals(13, count($instance_types[0]->getParameters()));
  }

  public function testCanRemoveInstanceTypesFromCache() {
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('instance_types_1'), "Cache Storage Adapter already had instance_types item");
    $standin_adapter->setItem('instance_types_1', "foo");
    $this->assertTrue($standin_adapter->hasItem('instance_types_1'), "Cache Storage Adapter did not have instance_types item");
    $cached_client->removeInstanceTypes(1);
    $this->assertFalse($standin_adapter->hasItem('instance_types_1'), "Cache Storage Adapter still had instance_types_1 item after invalidation/exipration");
  }

  public function testCanReplaceInstanceTypesInCache() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/instance_types/json/response',
        '1.5/instance_types/json/response'
      )
    );
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('instance_types_1'), "Cache Storage Adapter already had instance_types item");
    $standin_adapter->setItem('instance_types_1', array());
    $this->assertTrue($standin_adapter->hasItem('instance_types_1'), "Cache Storage Adapter did not have instance_types item");
    $this->assertEquals(array(), $cached_client->getInstanceTypes(1));

    $instance_types = $cached_client->updateInstanceTypes(1);

    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));

    $this->assertEquals(count($instance_types), count($cached_client->getInstanceTypes(1)));
    $instance_types = $cached_client->getInstanceTypes(1);
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\InstanceType', $instance_types[0]);
    $this->assertEquals(13, count($instance_types[0]->getParameters()));
  }

  public function testFetchesDatacentersFromApiWhenNotCached() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/datacenters/json/response',
        '1.5/datacenters/json/response'
      )
    );
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $this->assertFalse($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter already had datacenters_1 item");

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');
    $dcs = $cached_client->getDatacenters(1);

    $this->assertTrue($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter did not have datacenters_1 item");
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));
    $this->assertEquals(count($dcs), count($cached_client->getDatacenters(1)));
    $dcs = $cached_client->getDatacenters(1);
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\DataCenter', $dcs[0]);

    $dcs = $cached_client->getDatacenters(2);

    $this->assertTrue($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter did not have datacenters_1 item");
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(2, count($requests));
    $this->assertEquals(count($dcs), count($cached_client->getDatacenters(2)));
    $dcs = $cached_client->getDatacenters(2);
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\DataCenter', $dcs[0]);
    $this->assertEquals(7, count($dcs[0]->getParameters()));
  }

  public function testCanRemoveDatacentersFromCache() {
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter already had datacenters_1 item");
    $standin_adapter->setItem('datacenters_1', "foo");
    $this->assertTrue($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter did not have datacenters_1 item");
    $cached_client->removeDatacenters(1);
    $this->assertFalse($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter still had datacenters_1 item after invalidation/exipration");
  }

  public function testCanReplaceDatacentersInCache() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),
      array(
        '1.5/datacenters/json/response',
        '1.5/datacenters/json/response'
      )
    );
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter already had datacenters_1 item");
    $standin_adapter->setItem('datacenters_1', array());
    $this->assertTrue($standin_adapter->hasItem('datacenters_1'), "Cache Storage Adapter did not have datacenters_1 item");
    $this->assertEquals(array(), $cached_client->getDatacenters(1));

    $dcs = $cached_client->updateDatacenters(1);

    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));

    $this->assertEquals(count($dcs), count($cached_client->getDatacenters(1)));
    $dcs = $cached_client->getDatacenters(1);
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\DataCenter', $dcs[0]);
    $this->assertEquals(7, count($dcs[0]->getParameters()));
  }

  public function testFetchesServerTemplatesFromApiWhenNotCached() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),array('1.5/server_templates/json/with_filter/response'));
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $this->assertFalse($standin_adapter->hasItem('server_templates'), "Cache Storage Adapter already had server_templates item");

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');
    $sts = $cached_client->getServerTemplates();

    $this->assertTrue($standin_adapter->hasItem('server_templates'), "Cache Storage Adapter did not have server_templates item");
    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));

    $this->assertEquals(count($sts), count($cached_client->getServerTemplates()));
    $sts = $cached_client->getServerTemplates();
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\ServerTemplate', $sts[0]);
    $this->assertEquals(7, count($sts[0]->getParameters()));
  }

  public function testCanRemoveServerTemplatesFromCache() {
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('server_templates'), "Cache Storage Adapter already had server_templates item");
    $standin_adapter->setItem('server_templates', "foo");
    $this->assertTrue($standin_adapter->hasItem('server_templates'), "Cache Storage Adapter did not have server_templates item");
    $cached_client->removeServerTemplates();
    $this->assertFalse($standin_adapter->hasItem('server_templates'), "Cache Storage Adapter still had server_templates item after invalidation/exipration");
  }

  public function testCanReplaceServerTemplatesInCache() {
    $this->_guzzletestcase->setMockResponse(ClientFactory::getClient("1.5"),array('1.5/server_templates/json/with_filter/response'));
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $standin_adapter);

    $cached_client = $this->getApplicationServiceLocator()->get('RightScaleAPICache');

    $this->assertFalse($standin_adapter->hasItem('server_templates'), "Cache Storage Adapter already had server_templates item");
    $standin_adapter->setItem('server_templates', array());
    $this->assertTrue($standin_adapter->hasItem('server_templates'), "Cache Storage Adapter did not have server_templates item");
    $this->assertEquals(array(), $cached_client->getServerTemplates());

    $sts = $cached_client->updateServerTemplates();

    $requests = $this->_guzzletestcase->getMockedRequests();
    $this->assertEquals(1, count($requests));

    $this->assertEquals(count($sts), count($cached_client->getServerTemplates()));
    $sts = $cached_client->getServerTemplates();
    $this->assertInstanceOf('RGeyer\Guzzle\Rs\Model\Mc\ServerTemplate', $sts[0]);
    $this->assertEquals(7, count($sts[0]->getParameters()));
  }
}