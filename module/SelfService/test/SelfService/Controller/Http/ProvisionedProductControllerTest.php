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

  public function testIndexActionHasCorrectActionsForProvisionedProducts() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $service = $this->getProvisionedProductEntityService();
    $pp = $service->create(array());

    $this->dispatch('/provisionedproducts');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQuery("//a[@href='/provisionedproducts/$pp->id/show']");
    $this->assertXpathQuery("//a[@href='/provisionedproducts/$pp->id/cleanup']");
  }

  public function testCleanupActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $provisionedProduct = $this->getProvisionedProductEntityService()->create(array());
    $this->dispatch('/provisionedproducts/'.$provisionedProduct->id.'/cleanup');

    $response = strval($this->getResponse());

    $this->assertActionName('cleanup');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testShowActionCanBeAccessed() {
    $this->markTestSkipped("Show is unecessarily complex to mock and test.  Need to refactor a lot of the internal goodies into services, the controller shouldn't be doing this much work");
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->getApplicationServiceLocator()->setAllowOverride(true);

    $mockservers = array();
    $mockserver = array(
      'name' => 'name',
      'state' => 'inactive',
      'created_at' => new \DateTime(),
      'href' => '/api/foo/123'
    );
    $mockservers[] = (object)$mockserver;
    $apiServerModel = $this->getMock("ServerModel");
    $apiServerModel->expects($this->once())
      ->method('index')
      ->will($this->returnValue($mockservers));
    $clientMock = $this->getMockBuilder("\RGeyer\Guzzle\Rs\RightScaleApiClient")
      ->disableOriginalConstructor()
      ->getMock();
    $clientMock->expects($this->once())
      ->method('newModel')
      ->will($this->returnValue($apiServerModel));
    $this->getApplicationServiceLocator()->setService('RightScaleAPIClient', $clientMock);

    $prov_prod = new \SelfService\Document\ProvisionedProduct();
    $prov_prod->id = 'abc123';
    $prov_prod->provisioned_objects[] = new \SelfService\Document\ProvisionedObject(
      array(
        'type' => 'server',
        'href' => '/api/foo/123'
      )
    );

    $provprodservicemock = $this->getMockBuilder("\SelfService\Service\Entity\ProvisionedProductService")
      ->disableOriginalConstructor()
      ->getMock();

    $provprodservicemock->expects($this->once())
      ->method('find')
      ->will($this->returnValue($prov_prod));

    $this->getApplicationServiceLocator()->setService("SelfService\Service\Entity\ProvisionedProductService", $provprodservicemock);



    $this->dispatch('/provisionedproducts/abc123/show');

    $response = strval($this->getResponse());

    $this->assertActionName('show');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
  }

  public function testServerStartActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $service = $this->getProvisionedProductEntityService();
    $pp = $service->create(array());
    $this->dispatch('/provisionedproducts/'.$pp->id.'/serverstart');

    $response = strval($this->getResponse());

    $this->assertActionName('serverstart');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);}

  public function testServerStopActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $service = $this->getProvisionedProductEntityService();
    $pp = $service->create(array());
    $this->dispatch('/provisionedproducts/'.$pp->id.'/serverstop');

    $response = strval($this->getResponse());

    $this->assertActionName('serverstop');
    $this->assertControllerName('selfservice\controller\provisionedproduct');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);}
}