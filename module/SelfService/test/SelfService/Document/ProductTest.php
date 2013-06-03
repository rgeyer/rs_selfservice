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

  public function testCanMergeMetaInputs() {
    $product = new \SelfService\Document\Product();
    $sg = new \SelfService\Document\SecurityGroup();
    $meta_name = new \SelfService\Document\TextProductInput();
    $meta_name->id = "foo_id";
    $meta_name->input_name = "foo";

    $sg->name = array(
      "ref" => "text_product_input",
      "id" => "foo_id"
    );

    $desc = new \SelfService\Document\TextProductInput();
    $desc->id = "desc_id";
    $desc->input_name = "bar";
    $desc->default_value = "baz";

    $sg->description = array(
      "ref" => "text_product_input",
      "id" => "desc_id"
    );
    $product->resources[] = $sg;
    $product->resources[] = $meta_name;
    $product->resources[] = $desc;

    $product->mergeMetaInputs(array('foo' => 'bar'));
    $this->assertEquals('bar', $sg->name);
    $this->assertEquals('baz', $sg->description);
  }
}