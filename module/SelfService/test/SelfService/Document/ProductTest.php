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

    $product = $productService->find($product->id);

    $product->mergeMetaInputs(array());
    $this->assertEquals(0, $product->resources->count());
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

    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(0, count($product->resources));
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

    $product = $productService->find($product->id);

    $params = array('foo' => 'bar');
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(1, $product->resources[1]->schedule->count());
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

    $product = $productService->find($product->id);

    $params = array();
    $product->mergeMetaInputs($params);
    $product->resolveDepends($params);
    $this->assertEquals(0, count($product->resources));
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

    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource();

    $this->assertObjectHasAttribute("id", $product->resources[1]->security_group_rules[0]);
    $this->assertEquals("rule", $product->resources[1]->security_group_rules[0]->id);
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

    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource();

    $this->assertEquals(2, $product->resources->count());
    $this->assertEquals("security_group", $product->resources[1]->security_group_rules[0]->ingress_group["ref"]);
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

    $product = $productService->find($product->id);
    $product->replaceRefsWithConcreteResource(true);

    $this->assertEquals(2, $product->resources->count());
  }
}