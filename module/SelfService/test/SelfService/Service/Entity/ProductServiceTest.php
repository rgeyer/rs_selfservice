<?php

namespace SelfServiceTest\Service;

use SelfService\Entity\Provisionable\Product;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductServiceTest extends AbstractHttpControllerTestCase {

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

  public function testCanCreateProductFromRideJson() {
    $ridepayload = <<<EOF
[{"type":"Deployment","nickname":"lj"},{"type":"Server","publication_id":"46554","revision":"102","name":"DB_MYSQL55_13_2_1","st_name":"Database Manager for MySQL 5.5 (v13.2.1)","inputs":{"sys_dns/choice":"text:DNSMadeEasy","sys_dns/password":"text:password","sys_dns/user":"text:user","db/backup/lineage":"text:changeme"},"info":{"nickname":"Database Manager for MySQL 5.5 (v13.2.1) #1"},"allowOverride":["sys_dns/password","sys_dns/user"]}]
EOF;

    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');

    $productService->createFromRideJson($ridepayload);

    $products = $productService->findAll();
    $this->assertEquals(1, count($products));
    $this->assertEquals('lj', $products[0]->name);
    # Two "default" inputs (cloud and instance type), and the two overrides defined in the $ridepayload above
    $this->assertEquals(4, count($products[0]->meta_inputs));
  }

  public function testCanDeleteProductIncludingAllSubordinates() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');

    $productService->remove(1);

    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\AlertSpec')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\AlertSubjectBase')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\Product')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\SecurityGroup')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\SecurityGroupRule')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\Server')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\ServerArray')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\ServerTemplate')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\MetaInputs\ProductMetaInputBase')->findAll()));
  }

  public function testCanUpdateName() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    $productService->update(1, array('name' => 'foobar'));

    $product = $productService->find(1);
    $this->assertEquals('foobar', $product->name);
  }

  public function testCanUpdateIcon() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    $productService->update(1, array('icon_filename' => 'foobar.png'));

    $product = $productService->find(1);
    $this->assertEquals('foobar.png', $product->icon_filename);
  }

  public function testCanUpdateLaunchServers() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    $productService->update(1, array('launch_servers' => true));

    $product = $productService->find(1);
    $this->assertTrue($product->launch_servers);
  }

  public function testCanConvertToJson() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    print $productService->toJson(1);
  }

}