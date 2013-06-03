<?php

namespace SelfServiceTest\Service;

use SelfService\Entity\ProvisionedProduct;
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

  # TODO: This is the only test for BaseEntityService::create, need to refactor and have a test
  # for the abstract base class
  public function testCanCreateProvisionedProduct() {
    $provisionedProductService = $this->getProvisionedProductService();
    $provisionedProduct = $provisionedProductService->create(array());
    $provisionedProduct = $provisionedProductService->find($provisionedProduct->id);
    $this->assertNotNull($provisionedProduct->createdate);
    $this->assertLessThan(60, (time() - $provisionedProduct->createdate->sec));
  }

}