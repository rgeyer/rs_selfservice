<?php

namespace SelfServiceTest\Service;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProvisionedProductServiceTest extends AbstractHttpControllerTestCase {

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

  /**
   * @return \Doctrine\ODM\MongoDB\DocumentManager
   */
  protected function getDocumentManager() {
    return $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
  }

  # TODO: This is the only test for BaseEntityService::create, need to refactor and have a test
  # for the abstract base class
  public function testCanCreateProvisionedProduct() {
    $sm = $this->getApplicationServiceLocator();
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $standin_adapter);
    $provisionedProductService = $this->getProvisionedProductService();
    $provisionedProduct = $provisionedProductService->create(array());
    $this->getDocumentManager()->clear();
    $provisionedProduct = $provisionedProductService->find($provisionedProduct->id);
    $this->assertNotNull($provisionedProduct->createdate, "Create date was not automatically set by the service");
    $now = time();
    $createdate = $provisionedProduct->createdate->getTimestamp();
    $this->assertLessThan(60, ($now - $createdate));
  }

  public function testCreateSetsOwnerToNullWhenNoUserLoggedIn() {
    $sm = $this->getApplicationServiceLocator();
    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $standin_adapter);
    $provisionedProductService = $this->getProvisionedProductService();
    $provisionedProduct = $provisionedProductService->create(array());
    $this->getDocumentManager()->clear();
    $provisionedProduct = $provisionedProductService->find($provisionedProduct->id);
    $this->assertNull($provisionedProduct->owner, "Owner was expected to be null, but was set to something");
  }

  public function testCreateSetsOwnerToReferenceWhenUserIsLoggedIn() {
    \SelfServiceTest\Helpers::authenticateAsAdmin($this->getApplicationServiceLocator());

    $provisionedProductService = $this->getProvisionedProductService();
    $provisionedProduct = $provisionedProductService->create(array());
    $this->getDocumentManager()->clear();
    $provisionedProduct = $provisionedProductService->find($provisionedProduct->id);
    $this->assertNotNull($provisionedProduct->owner, "Owner was expected to be set, but was null");
    $this->assertInstanceOf('\SelfService\Document\User', $provisionedProduct->owner);
  }

  public function testCanAddProvisionedObject() {
    # To handle all the mocking of caches etc.
    \SelfServiceTest\Helpers::authenticateAsAdmin($this->getApplicationServiceLocator());
    $service = $this->getProvisionedProductService();

    # Create a product
    $pp = $service->create(array());
    $this->getDocumentManager()->clear();

    # Fetch the product
    $pp = $service->find($pp->id);
    $this->assertEquals(0, count($pp->provisioned_objects));
    $this->getDocumentManager()->clear();

    # Add a provisioned object
    $service->addProvisionedObject($pp->id, array('href' => '/api/object/1', 'type' => 'foo'));
    $this->getDocumentManager()->clear();

    # Fetch it again after adding
    $pp = $service->find($pp->id);
    $this->assertEquals(1, count($pp->provisioned_objects));

    $this->assertEquals('/api/object/1', $pp->provisioned_objects[0]->href);
  }

}