<?php

namespace SelfServiceTest\Controller\Api;

use Zend\Http\Request;
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

  public function testCreateCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/provisionedproduct', Request::METHOD_POST);

    $this->assertActionName('create');
    $this->assertControllerName('selfservice\controller\api\provisionedproduct');
    $this->assertResponseStatusCode(201);
    $this->assertHasResponseHeader('Location');
    $this->assertRegExp(',api/provisionedproduct/1,', strval($this->getResponse()));
  }

  public function testDeleteCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/provisionedproduct/1', Request::METHOD_DELETE);

    $this->assertResponseStatusCode(501);
  }

  public function testGetCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/provisionedproduct/1', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testGetListCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/provisionedproduct', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testUpdateCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/provisionedproduct/1', Request::METHOD_PUT);

    $this->assertResponseStatusCode(501);
  }
}