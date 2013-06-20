<?php

namespace SelfServiceTest\Provisioner;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class RsApiProvisionerTest extends AbstractHttpControllerTestCase {

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

  public function testProvisionPersistsProvisionedProductDocumentWhenSuccessful() {}

  public function testProvisionPersistsProvisionedProductDocumentWhenException() {}

}