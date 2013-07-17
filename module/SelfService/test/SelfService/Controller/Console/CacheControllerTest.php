<?php

namespace SelfServiceTest\Controller\Console;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class CacheControllerTest extends AbstractConsoleControllerTestCase {
  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();

    $serviceManager = $this->getApplicationServiceLocator();
  }

  public function testUpdateRightScaleActionCanBeAccessed() {
    $cache_service_mock = $this->getMockBuilder('SelfService\Service\CacheService')->disableOriginalConstructor()->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('CacheService', $cache_service_mock);
    $this->getApplicationServiceLocator()->setService('logger', $this->getMock('\Zend\Log\Logger'));

    $this->dispatch('cache update rightscale');

    $this->assertActionName('updaterightscale');
    $this->assertControllerName('selfservice\controller\cache');
    $this->assertResponseStatusCode(0);
  }
}