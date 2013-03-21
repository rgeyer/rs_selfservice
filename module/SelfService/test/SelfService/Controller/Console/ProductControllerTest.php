<?php

namespace SelfServiceTest\Controller\Console;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class ProductControllerTest extends AbstractConsoleControllerTestCase {
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

  public function testConsoleAddActionCanBeAccessed() {
    $this->dispatch('product add');

    print "This is just to see what the response is that's failing exclusively in travis-ci\n";
    print strval($this->getResponse());

    $this->assertActionName('consoleadd');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(0);
  }
}