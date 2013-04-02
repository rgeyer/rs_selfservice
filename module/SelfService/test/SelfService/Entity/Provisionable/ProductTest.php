<?php

namespace SelfServiceTest\Entity\Provisionable;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductTest extends AbstractHttpControllerTestCase {
  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();

    $serviceManager = $this->getApplicationServiceLocator();
  }

  public function testCanMergeMetaInputs() {
    $product = new \SelfService\Entity\Provisionable\Product();
    $sg = new \SelfService\Entity\Provisionable\SecurityGroup();
    $meta_name = new \SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput("foo");
    $meta_name->input_name = "foo";
    $sg->name = $meta_name;
    $product->security_groups[] = $sg;
    $product->parameters = array();

    $product->mergeMetaInputs(array('foo' => 'bar'));
    $this->assertEquals('bar', $sg->name->getVal());
  }
}