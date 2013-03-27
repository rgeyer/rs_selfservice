<?php

namespace SelfServiceTest\Controller\Plugin;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class GoogleAuthPluginTest extends AbstractHttpControllerTestCase {

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();

    $serviceManager = $this->getApplicationServiceLocator();

    // Initialize the schema.. Maybe I should register a module for clearing the schema/data
    // and/or loading mock test data
    $em = $serviceManager->get('doctrine.entitymanager.orm_default');
    $cli = new \Symfony\Component\Console\Application("PHPUnit Bootstrap", 1);
    $cli->setAutoExit(false);
    $helperSet = $cli->getHelperSet();
    $helperSet->set(new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em), 'em');
    $cli->addCommands(array(new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand()));
    $cli->run(
      new \Symfony\Component\Console\Input\ArrayInput(array('orm:schema-tool:create')),
      new \Symfony\Component\Console\Output\NullOutput()
    );
  }

  public function testRedirectsToLoginWhenNotAuthenticated() {
    $storage_adapter = $this->getMockForAbstractClass('Zend\Cache\Storage\Adapter\AbstractAdapter');

    $sm = $this->getApplicationServiceLocator();
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
    $this->assertRedirectRegex(',^.*/user,');
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