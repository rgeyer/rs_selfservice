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

  /**
   * Tests that resources in the product/resources array get product inputs replaced
   * properly.  In actual fact all resources will be "top level" since the import
   * process places them all there and the references get decorated with the "nested"
   * property.
   */
  public function testMergeMetaInputsResolvesInputRefsOnTopLevelResources() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();
    $sg = new \SelfService\Document\SecurityGroup();
    $meta_name = new \SelfService\Document\TextProductInput();
    $meta_name->id = "foo_id";
    $meta_name->input_name = "foo";

    $sg->name = new \stdClass();
    $sg->name->ref = "text_product_input";
    $sg->name->id = "foo_id";

    $desc = new \SelfService\Document\TextProductInput();
    $desc->id = "desc_id";
    $desc->input_name = "bar";
    $desc->default_value = "baz";

    $sg->description = new \stdClass();
    $sg->description->ref = "text_product_input";
    $sg->description->id = "desc_id";

    $product->resources[] = $sg;
    $product->resources[] = $meta_name;
    $product->resources[] = $desc;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $product->mergeMetaInputs(array('foo' => 'bar'));
    $this->assertEquals('bar', $sg->name);
    $this->assertEquals('baz', $sg->description);
  }

  public function testMergeMetaInputsResolvesInputRefsOnResourcesInEmbedManyArrays() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();

    $max_input = new \SelfService\Document\TextProductInput();
    $max_input->id = "foo_id";
    $max_input->input_name = "foo";
    $max_input->default_value = "10";

    $elasticity_params = new \SelfService\Document\ElasticityParams();
    $schedule1 = new \SelfService\Document\ElasticityParamsSchedule();
    $schedule2 = new \SelfService\Document\ElasticityParamsSchedule();

    $schedule1->max_count = new \stdClass();
    $schedule1->max_count->ref = "text_product_input";
    $schedule1->max_count->id = "foo_id";

    $schedule2->max_count = new \stdClass();
    $schedule2->max_count->ref = "text_product_input";
    $schedule2->max_count->id = "foo_id";

    $elasticity_params->schedule[] = $schedule1;
    $elasticity_params->schedule[] = $schedule2;

    $product->resources[] = $elasticity_params;
    $product->resources[] = $max_input;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $product->mergeMetaInputs(array('foo' => '5'));
    $this->assertEquals('5', $product->resources[0]->schedule[0]->max_count);
    $this->assertEquals('5', $product->resources[0]->schedule[1]->max_count);
  }

  public function testMergeMetaInputsExcludesDepends() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();
    $sg = new \SelfService\Document\SecurityGroup();
    $meta_name = new \SelfService\Document\TextProductInput();
    $meta_name->id = "foo_id";
    $meta_name->input_name = "foo";

    $sg->name = new \stdClass();
    $sg->name->ref = "text_product_input";
    $sg->name->id = "foo_id";

    $depends = new \stdClass();
    $depends->ref = "text_product_input";
    $depends->id = "foo_id";
    $depends->value = array("baz");
    $sg->depends = $depends;

    $desc = new \SelfService\Document\TextProductInput();
    $desc->id = "desc_id";
    $desc->input_name = "bar";
    $desc->default_value = "baz";

    $sg->description = new \stdClass();
    $sg->description->ref = "text_product_input";
    $sg->description->id = "desc_id";

    $product->resources[] = $sg;
    $product->resources[] = $meta_name;
    $product->resources[] = $desc;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $this->assertEquals($depends, $sg->depends);
  }

  public function testMergeMetaInputsSetsMissingInputRefsToNull() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();
    $sg = new \SelfService\Document\SecurityGroup();

    $sg->name = new \stdClass();
    $sg->name->ref = "text_product_input";
    $sg->name->id = "foo_id";

    $product->resources[] = $sg;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $product->mergeMetaInputs(array('foo' => 'bar'));
    $this->assertNull($product->resources[0]->name);
  }

  public function testResolveDependsRemovesUnmatchedResourceFromTopLevelResources() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();
    $sg = new \SelfService\Document\SecurityGroup();
    $meta_name = new \SelfService\Document\TextProductInput();
    $meta_name->id = "foo_id";
    $meta_name->input_name = "foo";

    $sg->name = new \stdClass();
    $sg->name->ref = "text_product_input";
    $sg->name->id = "foo_id";

    $depends = new \stdClass();
    $depends->ref = "text_product_input";
    $depends->id = "foo_id";
    $depends->value = array("baz");
    $sg->depends = $depends;

    $desc = new \SelfService\Document\TextProductInput();
    $desc->id = "desc_id";
    $desc->input_name = "bar";
    $desc->default_value = "baz";

    $sg->description = new \stdClass();
    $sg->description->ref = "text_product_input";
    $sg->description->id = "desc_id";

    $product->resources[] = $sg;
    $product->resources[] = $meta_name;
    $product->resources[] = $desc;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(2, count($product->resources));
  }

  public function testResolveDependsRemovesUnmatchedResourceFromEmbedManyArrays() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();

    $max_input = new \SelfService\Document\TextProductInput();
    $max_input->id = "foo_id";
    $max_input->input_name = "foo";
    $max_input->default_value = "10";

    $elasticity_params = new \SelfService\Document\ElasticityParams();
    $schedule1 = new \SelfService\Document\ElasticityParamsSchedule();
    $schedule2 = new \SelfService\Document\ElasticityParamsSchedule();

    $schedule1->max_count = new \stdClass();
    $schedule1->max_count->ref = "text_product_input";
    $schedule1->max_count->id = "foo_id";

    $schedule2->max_count = new \stdClass();
    $schedule2->max_count->ref = "text_product_input";
    $schedule2->max_count->id = "foo_id";

    $depends = new \stdClass();
    $depends->ref = "text_product_input";
    $depends->id = "foo_id";
    $depends->value = array("5");
    $schedule1->depends = $depends;

    $elasticity_params->schedule[] = $schedule1;
    $elasticity_params->schedule[] = $schedule2;

    $product->resources[] = $elasticity_params;
    $product->resources[] = $max_input;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(1, count($product->resources[0]->schedule));
  }

  public function testResolveDependsHandlesEmptyArrayForParams() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();
    $sg = new \SelfService\Document\SecurityGroup();
    $meta_name = new \SelfService\Document\TextProductInput();
    $meta_name->id = "foo_id";
    $meta_name->input_name = "foo";

    $sg->name = new \stdClass();
    $sg->name->ref = "text_product_input";
    $sg->name->id = "foo_id";

    $depends = new \stdClass();
    $depends->ref = "text_product_input";
    $depends->id = "foo_id";
    $depends->value = array("baz");
    $sg->depends = $depends;

    $desc = new \SelfService\Document\TextProductInput();
    $desc->id = "desc_id";
    $desc->input_name = "bar";
    $desc->default_value = "baz";

    $sg->description = new \stdClass();
    $sg->description->ref = "text_product_input";
    $sg->description->id = "desc_id";

    $product->resources[] = $sg;
    $product->resources[] = $meta_name;
    $product->resources[] = $desc;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $params = array();
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(2, count($product->resources));
  }

  public function testPruneBrokenRefsFromStandardArrays() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();
    $sg = new \SelfService\Document\SecurityGroup();
    $sg->id = "not_so_fast";

    $sgr = new \SelfService\Document\SecurityGroupRule();
    $sgr->id = "sgr";

    $ruleref = new \stdClass();
    $ruleref->ref = "security_group_rule";
    $ruleref->id = "foobar";
    $sg->security_group_rules[] = $ruleref;

    $ruleref = new \stdClass();
    $ruleref->ref = "security_group_rule";
    $ruleref->id = "sgr";
    $sg->security_group_rules[] = $ruleref;

    $product->resources[] = $sg;
    $product->resources[] = $sgr;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $product->pruneBrokenRefs();
    $this->assertEquals(1, count($product->resources[0]->security_group_rules));
  }

  public function testPruneBrokenRefsFromEmbedManyArrays() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $product = new \SelfService\Document\Product();

    $elasticity_params = new \SelfService\Document\ElasticityParams();
    $elasticity_params->id = "elas1";

    $depends = new \stdClass();
    $depends->ref = "text_product_input";
    $depends->id = "foo_id";
    $depends->value = array("5");
    $elasticity_params->depends = $depends;

    $paramref = new \stdClass();
    $paramref->ref = "elasticity_params";
    $paramref->id = "elas1";
    $array = new \SelfService\Document\ServerArray();
    $array->elasticity_params[] = $paramref;

    $product->resources[] = $array;
    $product->resources[] = $elasticity_params;

    $dm->persist($product);
    $dm->flush();

    $product = $dm->getRepository('SelfService\Document\Product')->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $product->pruneBrokenRefs();
    $this->assertEquals(0, count($product->resources[0]->elasticity_params));
  }
}