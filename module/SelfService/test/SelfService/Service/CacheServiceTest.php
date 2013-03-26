<?php

namespace SelfServiceTest\Service;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Cache\Storage\Adapter\Memory;

class CacheServiceTest extends AbstractHttpControllerTestCase {

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../config/application.config.php'
    );
    parent::setUp();
    $this->getApplicationServiceLocator();
  }

  public function testCanClearNamespace() {
    $storage_adapter = new Memory();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $storage_adapter);
    $storage_adapter->getOptions()->setNamespace("ns1");

    $cache_service = \SelfService\Service\CacheService::get($this->getApplicationServiceLocator());

    $storage_adapter->addItem("one", "one");
    $storage_adapter->addItem("two", "two");
    $item_count = 0;
    foreach($storage_adapter->getIterator() as $item) {
      $item_count++;
    }
    $this->assertEquals(2, $item_count);

    $cache_service->clearNamespace("ns1");
    $item_count = 0;
    foreach($storage_adapter->getIterator() as $item) {
      $item_count++;
    }
    $this->assertEquals(0, $item_count);
  }

  public function testCanUpdateClouds() {
    $storage_adapter = new Memory();
    $api_cache_mock = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")->disableOriginalConstructor()->getMock();
    $api_cache_mock->expects($this->once())
      ->method('updateClouds');
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $storage_adapter);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $api_cache_mock);

    $cache_service = \SelfService\Service\CacheService::get($this->getApplicationServiceLocator());
    $cache_service->updateClouds();
  }

  public function testCanUpdateInstanceTypes() {
    $storage_adapter = new Memory();

    $clouds_mock = array();
    $clouds_mock[0] = new \stdClass();
    $clouds_mock[0]->id = 1;
    $clouds_mock[1] = new \stdClass();
    $clouds_mock[1]->id = 2;

    $api_cache_mock = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")->disableOriginalConstructor()->getMock();
    $api_cache_mock->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue($clouds_mock));
    $api_cache_mock->expects($this->exactly(2))
      ->method('updateInstanceTypes')
      ->with($this->logicalOr(1,2));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $storage_adapter);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $api_cache_mock);

    $cache_service = \SelfService\Service\CacheService::get($this->getApplicationServiceLocator());
    $cache_service->updateInstanceTypes();
  }

  public function testCanUpdateDatacenters() {
    $storage_adapter = new Memory();

    $clouds_mock = array();
    $clouds_mock[0] = new \stdClass();
    $clouds_mock[0]->id = 1;
    $clouds_mock[1] = new \stdClass();
    $clouds_mock[1]->id = 2;

    $api_cache_mock = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")->disableOriginalConstructor()->getMock();
    $api_cache_mock->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue($clouds_mock));
    $api_cache_mock->expects($this->exactly(2))
      ->method('updateDatacenters')
      ->with($this->logicalOr(1,2));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $storage_adapter);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $api_cache_mock);

    $cache_service = \SelfService\Service\CacheService::get($this->getApplicationServiceLocator());
    $cache_service->updateDatacenters();
  }

  public function testCanUpdateServerTemplates() {
    $storage_adapter = new Memory();
    $api_cache_mock = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")->disableOriginalConstructor()->getMock();
    $api_cache_mock->expects($this->once())
      ->method('updateServerTemplates');
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('cache_storage_adapter', $storage_adapter);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $api_cache_mock);

    $cache_service = \SelfService\Service\CacheService::get($this->getApplicationServiceLocator());
    $cache_service->updateServerTemplates();
  }

}