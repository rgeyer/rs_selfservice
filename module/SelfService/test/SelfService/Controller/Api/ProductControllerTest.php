<?php

namespace SelfServiceTest\Controller\Api;

use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductControllerTest extends AbstractHttpControllerTestCase {

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
   * @return \SelfService\Service\Entity\ProductService
   */
  protected function getProductService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }

  /**
   * @return \Doctrine\ODM\MongoDB\DocumentManager
   */
  protected function getDocumentManager() {
    return $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
  }

  public function testCreateCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/product', Request::METHOD_POST);

    $this->assertResponseStatusCode(501);
  }

  public function testDeleteCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/product/abc123', Request::METHOD_DELETE);

    $this->assertResponseStatusCode(501);
  }

  public function testGetCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/product/abc123', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testListCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/product', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testUpdateCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/product/abc123', Request::METHOD_PUT);

    $this->assertResponseStatusCode(501);
  }

  public function testInputsCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $productServiceMock = $this->getMockBuilder("\SelfService\Service\Entity\ProductService")
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\ProductService', $productServiceMock);
    $this->dispatch('/api/product/abc123/inputs', Request::METHOD_POST);

    $this->assertResponseStatusCode(200);
  }

  public function testInputsReturns405OnNonPostMethod() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/product/abc123/inputs', Request::METHOD_PUT);

    $this->assertResponseStatusCode(405);
  }

  public function testInputsReturns404ForNonExistentProduct() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $productServiceMock = $this->getMockBuilder("\SelfService\Service\Entity\ProductService")
      ->getMock();
    $productServiceMock->expects($this->once())
      ->method('inputs')
      ->will($this->throwException(new \SelfService\Document\Exception\NotFoundException("foo","bar")));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\ProductService', $productServiceMock);

    $this->dispatch('/api/product/abc123/inputs', Request::METHOD_POST);

    $this->assertResponseStatusCode(404);
  }

  public function testInputsReturnsProperJson() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $mockcloud = new \stdClass();
    $mockcloud->href = "/api/clouds/1";
    $mockcloud->links = array();
    $mockrsapicache = $this->getMockBuilder("\SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $mockrsapicache->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($mockcloud)));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $mockrsapicache);
    $json = <<<EOF
    {
      "version": "1.0.0",
      "name": "foo",
      "resources": [
        {
          "id": "foo",
          "resource_type": "text_product_input"
        },
        {
          "id": "bar",
          "resource_type": "text_product_input"
        }
      ]
    }

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $product_id = $product->id;
    $this->getDocumentManager()->clear();

    $this->dispatch("/api/product/$product_id/inputs", Request::METHOD_POST);

    $this->assertResponseStatusCode(200);
    $jsonresponse = $this->getResponse()->getContent();
    $obj = json_decode($jsonresponse);
    $this->assertEquals(2, count($obj));
    $this->assertEquals('foo', $obj[0]->id);
  }
}