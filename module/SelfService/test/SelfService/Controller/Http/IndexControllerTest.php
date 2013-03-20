<?php

namespace SelfServiceTest\Controller\Http;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IndexControllerTest extends AbstractHttpControllerTestCase {
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
    $product = new \SelfService\Entity\Provisionable\Product();
    $product->launch_servers = false;
    $product->name = "foo";
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
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