<?php

namespace SelfServiceTest\Controller\Http;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductControllerTest extends AbstractHttpControllerTestCase {

  protected function mockProvisioningHelper() {
    return $this->getMockBuilder('SelfService\Service\ProvisioningHelper')
      ->disableOriginalConstructor()
      ->getMock();
  }

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

  public function testProvisionActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $sl = $this->getApplicationServiceLocator();
    $sl->setAllowOverride(true);
    $sl->setService('rs_provisioning_helper', $this->mockProvisioningHelper());
    $this->dispatch('/product/provision/1');

    $response = strval($this->getResponse());

    #print $response;

    $this->assertActionName('provision');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }
}