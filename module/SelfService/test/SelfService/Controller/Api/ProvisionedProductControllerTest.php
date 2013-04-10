<?php

namespace SelfServiceTest\Controller\Api;

use Zend\Http\Request;
use Zend\Http\Response;
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
    $this->dispatch('/api/provisionedproduct', Request::METHOD_POST);

    $this->assertActionName('create');
    $this->assertControllerName('selfservice\controller\api\provisionedproduct');
    $this->assertResponseStatusCode(201);
    $this->assertHasResponseHeader('Location');
    $this->assertRegExp(',api/provisionedproduct/1,', strval($this->getResponse()));
  }

  public function testDeleteCanBeAccessed() {
    $this->dispatch('/api/provisionedproduct/1', Request::METHOD_DELETE);

    $this->assertResponseStatusCode(501);
  }

  public function testGetCanBeAccessed() {
    $this->dispatch('/api/provisionedproduct/1', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testGetListCanBeAccessed() {
    $this->dispatch('/api/provisionedproduct', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testUpdateCanBeAccessed() {
    $this->dispatch('/api/provisionedproduct/1', Request::METHOD_PUT);

    $this->assertResponseStatusCode(501);
  }

  public function testObjectsCanBeAccessed() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    \SelfService\Product\php3tier::add($em);
    $product = $productService->find(1);
    $params = array(
      'product' => $product,
    );

    $provisionedProductService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
    $provisionedProductService->create($params);

    $this->getRequest()->setContent(
      json_encode(
        array(
          'type' => 'rs.deployment',
          'href' => 'http://foo.bar.baz'
        )
      )
    );
    $this->dispatch('/api/provisionedproduct/1/objects',Request::METHOD_POST);

    $this->assertResponseStatusCode(201);
  }

  public function testObjectsReturns405OnNonPostMethod() {
    $this->dispatch('/api/provisionedproduct/1/objects', Request::METHOD_PUT);

    $this->assertResponseStatusCode(405);
  }

  public function testObjectReturns400WhenTypeIsMissing() {
    $this->getRequest()->setContent(
      json_encode(
        array(
          'href' => 'http://foo.bar.baz'
        )
      )
    );

    $this->dispatch('/api/provisionedproduct/1/objects',Request::METHOD_POST);

    $response = strval($this->getResponse());

    $this->assertResponseStatusCode(Response::STATUS_CODE_400);
    $this->assertContains('missing', strtolower($response));
    $this->assertContains('type', strtolower($response));
  }

  public function testObjectReturns400WhenHrefIsMissing() {
    $this->getRequest()->setContent(
      json_encode(
        array(
          'type' => 'rs.deployment'
        )
      )
    );

    $this->dispatch('/api/provisionedproduct/1/objects',Request::METHOD_POST);

    $response = strval($this->getResponse());

    $this->assertResponseStatusCode(Response::STATUS_CODE_400);
    $this->assertContains('missing', strtolower($response));
    $this->assertContains('href', strtolower($response));
  }
}