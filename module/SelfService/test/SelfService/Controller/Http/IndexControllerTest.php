<?php

namespace SelfServiceTest\Controller\Http;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase {
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

  public function testIndexActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\index');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount("//div[@class='product']", 0);
  }

  public function testIndexActionRendersProducts() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $product = new \SelfService\Document\Product();
    $product->launch_servers = false;
    $product->name = "foo";
    $em = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $em->persist($product);
    $em->flush();

    $this->dispatch('/');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\index');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount("//div[@class='product']", 1);
  }

  public function testIndexActionRendersLeftArrowWhenMoreThanFourProducts() {

  }
}