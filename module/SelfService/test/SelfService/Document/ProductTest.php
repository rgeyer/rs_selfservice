<?php

namespace SelfServiceTest\Document;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductTest extends AbstractHttpControllerTestCase {
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

  public function testFoo() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();

    $deployment = new \SelfService\Document\Deployment();
    $deployment->id = "FooDepl";
    $deployment->name = "foo"; # array("rel" => "text_product_input", "id" => "foobarbaz");
    $deployment->server_tag_scope = "deployment";
    $deployment->depends = new \SelfService\Document\Depend();
    $deployment->depends->value = array('foo');
    $deployment->depends->id = "some_input";
    $deployment->depends->match = "any";
    $deployment->depends->ref = "text_product_input";

    $product->icon_filename = "foo.png";
    $product->launch_servers = true;
    $product->name = "Foo";
    $product->resources = array($deployment);

    $instance_type_input = new \SelfService\Document\InstanceTypeProductInput();

    $dm->persist($product);
    $dm->flush();

    $prods = $dm->getRepository("SelfService\Document\Product")->findAll();
    foreach($prods as $prod) {
      foreach($prod->resources as $doc) {
        print json_encode($doc);
        $this->assertTrue(is_string($doc->name));
      }
    }
  }
}