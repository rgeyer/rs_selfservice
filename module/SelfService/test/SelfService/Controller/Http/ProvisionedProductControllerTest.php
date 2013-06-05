<?php

namespace SelfServiceTest\Controller\Http;

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
  protected function getProvisionedProductEntityService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
  }

  public function testIndexActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/provisionedproducts');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
  }

  public function testCleanupActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $provisionedProduct = $this->getProvisionedProductEntityService()->create(array());
    $this->dispatch('/provisionedproducts/cleanup/'.$provisionedProduct->id);

    $response = strval($this->getResponse());

    $this->assertActionName('cleanup');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testShowActionCanBeAccessed() {
//    $this->dispatch('/provisionedproducts/show');
//
//    $response = strval($this->getResponse());
//
//    $this->assertActionName('show');
//    $this->assertControllerName('selfservice\controller\provisionedproduct');
//    $this->assertResponseStatusCode(200);
  }

  public function testServerStartActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/provisionedproducts/serverstart');

    $response = strval($this->getResponse());

    $this->assertActionName('serverstart');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);}

  public function testServerStopActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/provisionedproducts/serverstop');

    $response = strval($this->getResponse());

    $this->assertActionName('serverstop');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);}
}