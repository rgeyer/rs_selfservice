<?php

namespace SelfServiceTest\Controller\Http;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProvisionedProductControllerTest extends AbstractHttpControllerTestCase {

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

  public function testIndexActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/admin/provisionedproducts');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
  }

  public function testCleanupActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/admin/provisionedproducts/cleanup');

    $response = strval($this->getResponse());

    $this->assertActionName('cleanup');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testShowActionCanBeAccessed() {
//    $this->dispatch('/admin/provisionedproducts/show');
//
//    $response = strval($this->getResponse());
//
//    $this->assertActionName('show');
//    $this->assertControllerName('selfservice\controller\provisionedproduct');
//    $this->assertResponseStatusCode(200);
  }

  public function testServerStartActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/admin/provisionedproducts/serverstart');

    $response = strval($this->getResponse());

    $this->assertActionName('serverstart');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);}

  public function testServerStopActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/admin/provisionedproducts/serverstop');

    $response = strval($this->getResponse());

    $this->assertActionName('serverstop');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);}
}