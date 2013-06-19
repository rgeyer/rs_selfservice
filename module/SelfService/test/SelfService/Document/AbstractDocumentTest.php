<?php

namespace SelfServiceTest\Document;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class AbstractResourceTest extends AbstractHttpControllerTestCase {
  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../config/application.config.php'
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

  public function testHydratesScalarCollectionToScalar() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "Deployment",
      "resource_type": "deployment",
      "name": "name",
      "inputs": [ ],
      "servers": [ ],
      "server_arrays": [ ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($str);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $this->assertEquals("name", $product->resources[0]->name);
  }

}