<?php

namespace SelfServiceTest\Controller\Console;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class ProductControllerTest extends AbstractConsoleControllerTestCase {
  /**
   * @return \SelfService\Service\Entity\ProductService
   */
  protected function getProductEntityService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }

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

  public function testConsoleAddActionCanBeAccessed() {
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('logger', $this->getMock('\Zend\Log\Logger'));
    $productService = $this->getProductEntityService();
    $this->assertEquals(0, $productService->findAll()->count());
    $this->dispatch('product add php3tier');

    $this->assertActionName('consoleadd');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(0);
    $this->assertEquals(1, $productService->findAll()->count());
  }

  public function testConsoleAddAssumesManifestNameWhenPathFlagNotSet() {
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('logger', new \Zend\Log\Logger());
    $this->dispatch('product add php3tier');

    $this->assertActionName('consoleadd');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(0);
    $this->assertRegExp("/Loading manifest from .*php3tier.manifest.json/", $this->getResponse()->getContent());
  }

  public function testConsoleAddTriesRelativeOrAbsolutePathWhenPathFlagSet() {
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('logger', new \Zend\Log\Logger());
    $this->dispatch('product add ./foo/bar/baz.manifest.json --path');

    $this->assertActionName('consoleadd');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(0);
    $this->assertContains("No file existed at ./foo/bar/baz.manifest.json", $this->getResponse()->getContent());
  }

  public function testConsoleAddInformsUserWhenManifestDoesNotHaveProductJson() {
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('logger', new \Zend\Log\Logger());
    $this->dispatch('product add '.__DIR__.'/../../../products/empty.manifest.json --path');

    $this->assertActionName('consoleadd');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(0);
    $this->assertContains("No 'product_json' was specified", $this->getResponse()->getContent());
  }

  public function testConsoleAddInformsUserWhenManifestReferencesNonExistentProductJson() {
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('logger', new \Zend\Log\Logger());
    $this->dispatch('product add '.__DIR__.'/../../../products/invalidproductjson.manifest.json --path');

    $this->assertActionName('consoleadd');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(0);
    $this->assertContains("The file referenced by product_json does not exist", $this->getResponse()->getContent());
  }

  public function testConsoleAddActionRequiresProductName() {
    $this->dispatch('product add');

    $this->assertResponseStatusCode(1);
    $this->assertContains("Invalid arguments", $this->getResponse()->getContent());
  }
}