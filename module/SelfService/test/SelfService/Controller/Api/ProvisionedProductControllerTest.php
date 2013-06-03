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

  /**
   * @return \SelfService\Service\Entity\ProvisionedProductService
   */
  protected function getProvisionedProductService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
  }

  public function testCreateCanBeAccessed() {
    $this->dispatch('/api/provisionedproduct', Request::METHOD_POST);

    $this->assertActionName('create');
    $this->assertControllerName('selfservice\controller\api\provisionedproduct');
    $this->assertResponseStatusCode(201);
    $this->assertHasResponseHeader('Location');
    $this->assertRegExp(',api/provisionedproduct/[0-9a-z]+,', strval($this->getResponse()));
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
    $provisionedProductService = $this->getProvisionedProductService();
    $provisionedProduct = $provisionedProductService->create(array());

    $this->getRequest()->setContent(
      json_encode(
        array(
          'type' => 'rs.deployment',
          'href' => 'http://foo.bar.baz'
        )
      )
    );
    $this->dispatch(sprintf('/api/provisionedproduct/%s/objects', $provisionedProduct->id), Request::METHOD_POST);

    $this->assertResponseStatusCode(201);
  }

  public function testObjectsReturns405OnNonPostMethod() {
    $this->dispatch('/api/provisionedproduct/abc123/objects', Request::METHOD_PUT);

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