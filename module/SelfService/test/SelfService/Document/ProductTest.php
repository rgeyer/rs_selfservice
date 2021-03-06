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
   * @return \SelfService\Service\Entity\ProductService
   */
  protected function getProductService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }

  /**
   * @return \Doctrine\ODM\MongoDB\DocumentManager
   */
  protected function getDocumentManager() {
    return $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
  }

  /**
   * Tests that resources in the product/resources array get product inputs replaced
   * properly.  In actual fact all resources will be "top level" since the import
   * process places them all there and the references get decorated with the "nested"
   * property.
   */
  public function testMergeMetaInputsResolvesInputRefsOnTopLevelResources() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "desc_id",
      "resource_type": "text_product_input",
      "input_name": "bar",
      "default_value": "baz"
    },
    {
      "id": "security_group",
      "resource_type": "security_group",
      "name": { "ref": "text_product_input", "id": "foo_id" },
      "description": { "ref": "text_product_input", "id": "desc_id" }
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $product->mergeMetaInputs(array('foo' => 'bar'));
    $this->assertEquals('bar', $product->resources[2]->name);
    $this->assertEquals('baz', $product->resources[2]->description);
  }

  public function testMergeMetaInputsResolvesInputRefsOnResourcesInEmbedManyArrays() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "10"
    },
    {
      "id": "elast",
      "resource_type": "elasticity_params",
      "schedule": [
        {
          "max_count": { "ref": "text_product_input", "id": "foo_id" }
        },
        {
          "max_count": { "ref": "text_product_input", "id": "foo_id" }
        }
      ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $product->mergeMetaInputs(array('foo' => '5'));
    $this->assertEquals('5', $product->resources[1]->schedule[0]->max_count);
    $this->assertEquals('5', $product->resources[1]->schedule[1]->max_count);
  }

  public function testMergeMetaInputsExcludesDepends() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "security_group",
      "resource_type": "security_group",
      "name": "name",
      "description": "description",
      "depends": { "ref": "text_product_input", "id": "foo_id", "value": ["baz"] }
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $this->assertNotEquals("bar", $product->resources[1]->depends);
    $this->assertTrue(is_array($product->resources[1]->depends), "Depends property of security group was not an associative array (hash)");
    $this->assertArrayHasKey("ref", $product->resources[1]->depends);
    $this->assertArrayHasKey("id", $product->resources[1]->depends);
    $this->assertArrayHasKey("value", $product->resources[1]->depends);
  }

  public function testMergeMetaInputsSetsMissingInputRefsToNull() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "security_group",
      "resource_type": "security_group",
      "name": { "ref": "text_product_input", "id": "foo_id" },
      "description": "description"
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $product->mergeMetaInputs(array('foo' => 'bar'));
    $this->assertNull($product->resources[0]->name);
  }

  public function testMergeMetaInputsPrunesProductInputsFromTopLevelResources() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "desc_id",
      "resource_type": "text_product_input",
      "input_name": "bar",
      "default_value": "baz"
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $product->mergeMetaInputs(array());
    $this->assertEquals(0, $product->resources->count());
  }

  public function testMergeMetaInputsDoesNotScheduleRemovedResourcesForOdmRemoval() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "desc_id",
      "resource_type": "text_product_input",
      "input_name": "bar",
      "default_value": "baz"
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $product->mergeMetaInputs(array());
    $this->assertEquals(0, $product->resources->count());
    $this->assertFalse($product->resources->isDirty());
  }

  public function testMergeMetaInputsSetsInstanceTypeHrefsToNullWhenNoValueOrDefaultSpecified() {
    $service = $this->getProductService();

    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "cloud",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloud",
      "display_name": "Cloud",
      "description": "The cloud where the 3-Tier will be provisioned"
    },
    {
      "id": "instance_type",
      "resource_type": "instance_type_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "input_name": "instance_type",
      "display_name": "Doesn't Matter"
    },
    {
      "id": "instance",
      "resource_type": "instance",
      "cloud_href": { "ref": "cloud_product_input", "id": "cloud" },
      "instance_type_href": { "ref": "instance_type_product_input", "id": "instance_type" }
    }
  ]
}
EOF;

    $product = $service->createFromJson($json);
    $product->mergeMetaInputs(array());
    $this->assertNull($product->resources[2]->instance_type_href);
  }

  public function testMergeMetaInputsSetsInstanceTypeHrefsToDefaultIfSpecified() {
    $service = $this->getProductService();

    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "cloud",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloud",
      "display_name": "Cloud",
      "description": "The cloud where the 3-Tier will be provisioned"
    },
    {
      "id": "instance_type",
      "resource_type": "instance_type_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "default_value": [
        {
          "cloud_href": "/api/clouds/1",
          "resource_hrefs": ["/api/clouds/1/instance_types/CQQV62T389R32"]
        }
      ],
      "input_name": "instance_type",
      "display_name": "Doesn't Matter"
    },
    {
      "id": "instance",
      "resource_type": "instance",
      "cloud_href": { "ref": "cloud_product_input", "id": "cloud" },
      "instance_type_href": { "ref": "instance_type_product_input", "id": "instance_type" }
    }
  ]
}
EOF;

    $product = $service->createFromJson($json);

    $product->mergeMetaInputs(array());
    $this->assertEquals("/api/clouds/1/instance_types/CQQV62T389R32", $product->resources[2]->instance_type_href);
  }

  public function testMergeMetaInputsSetsInstanceTypeHrefsExplicitValueIfSpecified() {
    $service = $this->getProductService();

    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "cloud",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloud",
      "display_name": "Cloud",
      "description": "The cloud where the 3-Tier will be provisioned"
    },
    {
      "id": "instance_type",
      "resource_type": "instance_type_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "input_name": "instance_type",
      "display_name": "Doesn't Matter"
    },
    {
      "id": "instance",
      "resource_type": "instance",
      "cloud_href": { "ref": "cloud_product_input", "id": "cloud" },
      "instance_type_href": { "ref": "instance_type_product_input", "id": "instance_type" }
    }
  ]
}
EOF;

    $product = $service->createFromJson($json);
    $product->mergeMetaInputs(array('instance_type' => "/api/clouds/1/instance_types/ABC123"));
    $this->assertEquals("/api/clouds/1/instance_types/ABC123", $product->resources[2]->instance_type_href);
  }

  public function testResolveDependsRemovesUnmatchedResourceFromTopLevelResources() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "desc_id",
      "resource_type": "text_product_input",
      "input_name": "bar",
      "default_value": "baz"
    },
    {
      "id": "security_group",
      "resource_type": "security_group",
      "name": { "ref": "text_product_input", "id": "foo_id" },
      "description": { "ref": "text_product_input", "id": "desc_id" },
      "depends": { "ref": "text_product_input", "id": "foo_id", "value": ["baz"] }
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->resolveDepends($params);
    $product->mergeMetaInputs($params);
    $this->assertEquals(0, count($product->resources));
  }

  public function testResolveDependsRemovesUnmatchedResourceFromTopLevelResourcesWithoutSchedulingForOdmRemoval() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "desc_id",
      "resource_type": "text_product_input",
      "input_name": "bar",
      "default_value": "baz"
    },
    {
      "id": "security_group",
      "resource_type": "security_group",
      "name": { "ref": "text_product_input", "id": "foo_id" },
      "description": { "ref": "text_product_input", "id": "desc_id" },
      "depends": { "ref": "text_product_input", "id": "foo_id", "value": ["baz"] }
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(0, count($product->resources));
    $this->assertFalse($product->resources->isDirty());
  }

  public function testResolveDependsRemovesUnmatchedResourceFromEmbedManyArrays() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "10"
    },
    {
      "id": "elast",
      "resource_type": "elasticity_params",
      "schedule": [
        {
          "max_count": { "ref": "text_product_input", "id": "foo_id" },
          "depends": { "ref": "text_product_input", "id": "foo_id", "value": ["5"] }
        },
        {
          "max_count": { "ref": "text_product_input", "id": "foo_id" }
        }
      ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(1, $product->resources[1]->schedule->count());
  }

  public function testResolveDependsRemovesUnmatchedResourceFromEmbedManyArraysWithoutSchedulingForOdmRemoval() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "10"
    },
    {
      "id": "elast",
      "resource_type": "elasticity_params",
      "schedule": [
        {
          "max_count": { "ref": "text_product_input", "id": "foo_id" },
          "depends": { "ref": "text_product_input", "id": "foo_id", "value": ["5"] }
        },
        {
          "max_count": { "ref": "text_product_input", "id": "foo_id" }
        }
      ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(1, $product->resources[1]->schedule->count());
    $this->assertFalse($product->resources[1]->schedule->isDirty());
  }

  public function testResolveDependsHandlesEmptyArrayForParams() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "desc_id",
      "resource_type": "text_product_input",
      "input_name": "bar",
      "default_value": "baz"
    },
    {
      "id": "security_group",
      "resource_type": "security_group",
      "name": { "ref": "text_product_input", "id": "foo_id" },
      "description": { "ref": "text_product_input", "id": "desc_id" },
      "depends": { "ref": "text_product_input", "id": "foo_id", "value": ["baz"] }
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $params = array();
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(0, count($product->resources));
  }

  public function testResolveDependsDoesNotRemoveProductInputs() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "foo_id",
      "resource_type": "text_product_input",
      "input_name": "foo",
      "default_value": "foo"
    },
    {
      "id": "desc_id",
      "resource_type": "text_product_input",
      "input_name": "bar",
      "default_value": "baz",
      "depends": { "ref": "text_product_input", "id": "foo_id", "value": ["baz"] }
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->resolveDepends($params);
    $this->assertEquals(2, count($product->resources));
  }

  public function testPruneBrokenRefsFromStandardArrays() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "security_group",
      "resource_type": "security_group",
      "name": "name",
      "description": "description",
      "security_group_rules": [
        { "ref": "security_group_rule", "id": "foobar" },
        { "ref": "security_group_rule", "id": "sgr" }
      ]
    },
    {
      "id": "sgr",
      "resource_type": "security_group_rule"
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $product->pruneBrokenRefs();
    $this->assertEquals(1, count($product->resources[0]->security_group_rules));
  }

  public function testPruneBrokenRefsDoesNotUnsetNonRefProperties() {
    $json = <<<EOF
  {
    "version": "1.0.0",
    "name": "foo",
    "resources": [
      {
        "id": "security_group",
        "resource_type": "security_group",
        "name": "name",
        "description": "description",
        "security_group_rules": [
          { "ref": "security_group_rule", "id": "foobar" },
          { "ref": "security_group_rule", "id": "sgr" }
        ]
      },
      {
        "id": "sgr",
        "resource_type": "security_group_rule"
      }
    ]
  }
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);

    $product->pruneBrokenRefs();
    $this->assertNotNull($product->resources[0]->name);
  }

  public function testReplaceResourceRefsWithConcreteResourceInArray() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "rule",
      "resource_type": "security_group_rule",
      "protocol": "tcp",
      "cidr_ips": "0.0.0.0\/0",
      "source_type": "cidr_ips",
      "protocol_details": [
        {
          "end_port": "22",
          "start_port": "22"
        }
      ]
    },
    {
      "id": "group",
      "resource_type": "security_group",
      "name": "group",
      "security_group_rules": [
        { "ref": "security_group_rule", "id": "rule" }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource();

    $this->assertObjectHasAttribute("id", $product->resources[1]->security_group_rules[0]);
    $this->assertEquals("rule", $product->resources[1]->security_group_rules[0]->id);
  }

  public function testReplaceResourceRefsWithConcreteResourceDoesNotScheduleRemovedResourcesForOdmRemoval() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "rule",
      "resource_type": "security_group_rule",
      "protocol": "tcp",
      "cidr_ips": "0.0.0.0\/0",
      "source_type": "cidr_ips",
      "protocol_details": [
        {
          "end_port": "22",
          "start_port": "22"
        }
      ]
    },
    {
      "id": "group",
      "resource_type": "security_group",
      "name": "group",
      "security_group_rules": [
        { "ref": "security_group_rule", "id": "rule" }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource();

    $this->assertObjectHasAttribute("id", $product->resources[1]->security_group_rules[0]);
    $this->assertEquals("rule", $product->resources[1]->security_group_rules[0]->id);
    $this->assertFalse($product->resources->isDirty());
  }

  public function testReplaceResourceRefsWithConcreteResourceRemovesResolvedResource() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "rule",
      "resource_type": "security_group_rule",
      "protocol": "tcp",
      "cidr_ips": "0.0.0.0\/0",
      "source_type": "cidr_ips",
      "protocol_details": [
        {
          "end_port": "22",
          "start_port": "22"
        }
      ]
    },
    {
      "id": "group",
      "resource_type": "security_group",
      "name": "group",
      "security_group_rules": [
        { "ref": "security_group_rule", "id": "rule" }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource();

    $this->assertEquals(1, $product->resources->count());
  }

  public function testReplaceResourceRefsWithConcreteResourceDoesNotRemoveProtectedTypes() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "rule",
      "resource_type": "security_group_rule",
      "protocol": "tcp",
      "ingress_group": { "ref": "security_group", "id": "group" }
    },
    {
      "id": "group",
      "resource_type": "security_group",
      "name": "group",
      "security_group_rules": [
        { "ref": "security_group_rule", "id": "rule" }
      ]
    },
    {
      "id": "server_template",
      "resource_type": "server_template"
    },
    {
      "id": "instance",
      "resource_type": "instance",
      "security_groups": [
        { "ref": "security_group", "id": "group" }
      ],
      "server_template": [
        { "ref": "server_template", "id": "server_template" }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource();

    $this->assertEquals(2, $product->resources->count());
    $this->assertEquals("security_group", $product->resources[1]->security_group_rules[0]->ingress_group["ref"]);
  }

  public function testReplaceResourceRefsWithConcreteResourceDoesNotReplaceProtectedTypeRefs() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "rule",
      "resource_type": "security_group_rule",
      "protocol": "tcp",
      "ingress_group": { "ref": "security_group", "id": "group" }
    },
    {
      "id": "group",
      "resource_type": "security_group",
      "name": "group",
      "security_group_rules": [
        { "ref": "security_group_rule", "id": "rule" }
      ]
    },
    {
      "id": "server_template",
      "resource_type": "server_template"
    },
    {
      "id": "instance",
      "resource_type": "instance",
      "security_groups": [
        { "ref": "security_group", "id": "group" }
      ],
      "server_template": [
        { "ref": "server_template", "id": "server_template" }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource();

    $this->assertEquals(2, $product->resources->count());
    $this->assertTrue(is_array($product->resources[3]->security_groups), "Expected instance security groups property to be an array");
    $this->assertEquals(1, count($product->resources[3]->security_groups));
    $this->assertTrue(is_array($product->resources[3]->security_groups[0]), "Expected first instance security group record to be a ref array");
    $this->assertArrayHasKey("ref", $product->resources[3]->security_groups[0]);
  }

  public function testReplaceResourceRefsWithConcreteResourceCanLimitToNested() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "rule",
      "resource_type": "security_group_rule",
      "protocol": "tcp",
      "ingress_group": { "ref": "security_group", "id": "group" }
    },
    {
      "id": "group",
      "resource_type": "security_group",
      "name": "group",
      "security_group_rules": [
        { "ref": "security_group_rule", "id": "rule" }
      ]
    },
    {
      "id": "instance",
      "resource_type": "instance",
      "security_groups": [
        { "ref": "security_group", "id": "group" }
      ],
      "server_template": [
        {
          "id": "server_template",
          "resource_type": "server_template"
        }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource(true);

    $this->assertEquals(3, $product->resources->count());
  }

  public function testCanDedupeOnlyOneArrayOfReferenceOrNestedInTopLevelResource() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "instance",
      "resource_type": "instance",
      "server_template": [
        {
          "id": "server_template",
          "resource_type": "server_template"
        },
        {
          "id": "server_template_too",
          "resource_type": "server_template"
        }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->dedupeOnlyOneProperties();

    $this->assertInstanceOf("SelfService\Document\ServerTemplate", $product->resources[2]->server_template);
  }

  public function testCanDedupeOnlyOneArrayOfReferenceOrNestedInNestedResource() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "server",
      "resource_type": "server",
      "instance": [
        {
          "id": "instance",
          "resource_type": "instance",
          "server_template": [
            {
              "id": "server_template",
              "resource_type": "server_template"
            },
            {
              "id": "server_template_too",
              "resource_type": "server_template"
            }
          ]
        },
        {
          "id": "instance_too",
          "resource_type": "instance"
        }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->dedupeOnlyOneProperties();

    $this->assertInstanceOf("SelfService\Document\Instance", $product->resources[4]->instance);
    $this->assertInstanceOf("SelfService\Document\ServerTemplate", $product->resources[4]->instance->server_template);
  }

  public function testCanDedupeOnlyOneArrayOfEmbeddedInArrayOfEmbedded() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "elasticity_params",
      "resource_type": "elasticity_params",
      "queue_specific_params": [
        {
          "item_age": [
            {
              "algorithm": "foo",
              "max_age": "1",
              "regexp": ".*"
            }
          ]
        }
      ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->dedupeOnlyOneProperties();

    $this->assertInstanceOf("SelfService\Document\ElasticityParamsQueueSpecificParams", $product->resources[0]->queue_specific_params);
    $this->assertInstanceOf("SelfService\Document\ElasticityParamsQueueSpecificParamsItemAge", $product->resources[0]->queue_specific_params->item_age);
  }

  public function testCanDedupOnlyOneArrayOfRefOrNestedInArrayOfEmbedded() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "security_group",
      "resource_type": "security_group",
      "security_group_rules": [
        {
          "id": "rule1",
          "resource_type": "security_group_rule",
          "protocol_details": [
            {
              "end_port": "22",
              "start_port": "22"
            }
          ]
        }
      ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->dedupeOnlyOneProperties();

    $this->assertInstanceOf("SelfService\Document\SecurityGroupRuleProtocolDetail", $product->resources[1]->security_group_rules[0]->protocol_details);
  }

  public function testCanDedupeOnlyOneArrayOfEmbeddedInTopLevelResource() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "elasticity_params",
      "resource_type": "elasticity_params",
      "pacing": [
        {
          "resize_calm_time": "10",
          "resize_down_by": "1",
          "resize_up_by": "2"
        },
        {
          "resize_calm_time": "20",
          "resize_down_by": "2",
          "resize_up_by": "4"
        }
      ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->dedupeOnlyOneProperties();

    $this->assertInstanceOf("SelfService\Document\ElasticityParams", $product->resources[0]);
    $this->assertInstanceOf("SelfService\Document\ElasticityParamsPacing", $product->resources[0]->pacing);
    $this->assertEquals("10", $product->resources[0]->pacing->resize_calm_time);
  }

  public function testCanDedupeOnlyOneArrayOfEmbeddedInNestedResource() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "server_array",
      "resource_type": "server_array",
      "elasticity_params": [
        {
          "id": "elasticity_params1",
          "resource_type": "elasticity_params",
          "pacing": [
            {
              "resize_calm_time": "10",
              "resize_down_by": "1",
              "resize_up_by": "2"
            },
            {
              "resize_calm_time": "20",
              "resize_down_by": "2",
              "resize_up_by": "4"
            }
          ]
        },
        {
          "id": "elasticity_params2",
          "resource_type": "elasticity_params"
        }
      ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->dedupeOnlyOneProperties();

    $this->assertInstanceOf("SelfService\Document\ElasticityParams", $product->resources[2]->elasticity_params);
    $this->assertInstanceOf("SelfService\Document\ElasticityParamsPacing", $product->resources[2]->elasticity_params->pacing);
    $this->assertEquals("10", $product->resources[2]->elasticity_params->pacing->resize_calm_time);
  }

  public function testDedupeDoesNotClobberThingsWhichShouldBeArrays() {
    $json = <<<EOF
{
  "version": "1.0.0",
  "name": "foo",
  "resources": [
    {
      "id": "instance",
      "resource_type": "instance",
      "security_groups": [
        {
          "id": "security_group",
          "resource_type": "security_group"
        },
        {
          "id": "security_group_too",
          "resource_type": "security_group"
        }
      ]
    }
  ]
}

EOF;

    $productService = $this->getProductService();
    $product = $productService->createFromJson($json);
    $this->getDocumentManager()->clear();
    $product = $productService->find($product->id);
    $product->dedupeOnlyOneProperties();

    $this->assertTrue(is_array($product->resources[2]->security_groups));
    $this->assertEquals(2, count($product->resources[2]->security_groups));
    $this->assertArrayNotHasKey("ref", $product->resources[2]->security_groups);
    foreach($product->resources[2]->security_groups as $sg) {
      $this->assertTrue(is_array($sg), "Security Group was expected to be an array");
      $this->assertArrayHasKey("ref", $sg);
    }
  }
}