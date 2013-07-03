<?php

namespace SelfServiceTest\Controller\Plugin;

use Zend\Mvc\MvcEvent;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class GoogleAuthPluginTest extends AbstractHttpControllerTestCase {

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();

    $cli = $this->getApplicationServiceLocator()->get('doctrine.cli');
    $cli->setAutoExit(false);

    $cli->run(
      new \Symfony\Component\Console\Input\ArrayInput(array('odm:schema:drop')),
      new \Symfony\Component\Console\Output\NullOutput()
    );

    $cli->run(
      new \Symfony\Component\Console\Input\ArrayInput(array('odm:schema:create')),
      new \Symfony\Component\Console\Output\NullOutput()
    );
  }

  public function testRedirectOnUnauthorizedStopsEventPropogation() {
    $sm = $this->getApplicationServiceLocator();
    $storage_adapter = $this->getMockForAbstractClass('Zend\Cache\Storage\Adapter\AbstractAdapter');

    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $storage_adapter);

    $eventListenerMock = $this->getMock('Zend\Mvc\DispatchListener');
    $eventListenerMock->expects($this->never())
      ->method('onDispatch');

    $eventManager = $this->getApplication()->getEventManager();
    $eventManager->attach(MvcEvent::EVENT_DISPATCH, array($eventListenerMock, 'onDispatch'));

    $this->dispatch('/');
  }

  public function testRedirectsToLoginWhenNotAuthenticated() {
    $sm = $this->getApplicationServiceLocator();
    $storage_adapter = $this->getMockForAbstractClass('Zend\Cache\Storage\Adapter\AbstractAdapter');

    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $storage_adapter);

    $this->dispatch('/');

    $this->assertRedirect();
    $this->assertRedirectRegex(',^.*/login,');
  }

  public function testDoesNotRedirectWhenControllerIsLogin() {
    $this->dispatch('/login');

    $this->assertNotRedirect();
    $this->assertControllerName('selfservice\controller\login');
    $this->assertResponseStatusCode(200);
  }

  public function testDoesNotRedirectWhenAuthenticated() {
    $storage_adapter = $this->getMockForAbstractClass('Zend\Cache\Storage\Adapter\AbstractAdapter');

    $sm = $this->getApplicationServiceLocator();
    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $storage_adapter);

    \SelfServiceTest\Helpers::authenticateAsAdmin($this->getApplicationServiceLocator());
    $this->dispatch('/');

    $this->assertNotRedirect();
  }

  public function testStoresPreLoginRouteMatchInSession() {
    $this->markTestSkipped("Haven't properly implemented this yet.");
    $this->dispatch('home');
  }

  public function testRedirectsToUnauthorizedWhenAuthenticatedButNotAuthorized() {
    $storage_adapter = $this->getMockForAbstractClass('Zend\Cache\Storage\Adapter\AbstractAdapter');

    $sm = $this->getApplicationServiceLocator();
    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $storage_adapter);

    \SelfServiceTest\Helpers::authenticateAsUnauthorizedUser($this->getApplicationServiceLocator());
    $this->dispatch('/');

    $this->assertRedirect();
    $this->assertRedirectRegex(',^.*/user/unauthorized,');
  }

  public function testDoesNotRedirectWhenRouteIsUserUnauthorized() {
    $storage_adapter = $this->getMockForAbstractClass('Zend\Cache\Storage\Adapter\AbstractAdapter');

    $sm = $this->getApplicationServiceLocator();
    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $storage_adapter);
    \SelfServiceTest\Helpers::authenticateAsUnauthorizedUser($this->getApplicationServiceLocator());
    $this->dispatch('/user/unauthorized');

    $this->assertNotRedirect();
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(200);
  }
}