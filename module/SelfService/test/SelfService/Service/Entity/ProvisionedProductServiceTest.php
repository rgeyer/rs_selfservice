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

  # TODO: This is the only test for BaseEntityService::create, need to refactor and have a test
  # for the abstract base class
  public function testCanCreateProvisionedProduct() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    \SelfService\Product\php3tier::add($em);
    $product = $productService->find(1);
    $params = array(
      'product' => $product,
    );
    $provisionedProductService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
    $provisionedProductService->create($params);
    $provisionedProduct = $provisionedProductService->find(1);
    $this->assertEquals($product, $provisionedProduct->product);
    $this->assertNotNull($provisionedProduct->createdate);
    $this->assertLessThan(60, (time() - $provisionedProduct->createdate->getTimestamp()));
  }

}