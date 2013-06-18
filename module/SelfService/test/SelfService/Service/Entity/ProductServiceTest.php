<?php

namespace SelfServiceTest\Service;

use Zend\Http\Request as HttpRequest;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
 * TODO: Tests for conversion of each of the resource types to ensure
 * embedded ODM docs are handled properly.
 */
class ProductServiceTest extends AbstractHttpControllerTestCase {

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

  /**
   * @return \SelfService\Service\Entity\ProductService
   */
  protected function getProductService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }

  /**
   * @expectedException BadMethodCallException
   */
  public function testCreateIsBadMethodCall() {
    $productService = $this->getProductService();
    $productService->create(array());
  }

  public function testCanCreateProductFromRideJson() {
    $ridepayload = <<<EOF
[{"type":"Deployment","nickname":"lj"},{"type":"Server","publication_id":"46554","revision":"102","name":"DB_MYSQL55_13_2_1","st_name":"Database Manager for MySQL 5.5 (v13.2.1)","inputs":{"sys_dns/choice":"text:DNSMadeEasy","sys_dns/password":"text:password","sys_dns/user":"text:user","db/backup/lineage":"text:changeme"},"info":{"nickname":"Database Manager for MySQL 5.5 (v13.2.1) #1"},"allowOverride":["sys_dns/password","sys_dns/user"]}]
EOF;

    $inputtypesarray = array(
      "text_product_input",
      "instance_type_product_input",
      "cloud_product_input"
    );

    $productService = $this->getProductService();

    $productService->createFromRideJson($ridepayload);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      $this->assertEquals('lj', $product->name);
      $inputcount = 0;
      foreach($product->resources as $resource) {
        if(in_array($resource->resource_type, $inputtypesarray) && $resource->display) {
          $inputcount++;
        }
      }
      # Two "default" inputs (cloud and instance type), and the two overrides defined in the $ridepayload above
      $this->assertEquals(4, $inputcount);
    }
  }

  public function testCanCreateProductFromKitchenSinkJsonObject() {
    $str = file_get_contents(__DIR__ . '/../../../../../../json/input/kitchensink.json');
    $kitchenSinkJson = json_decode($str);

    $productService = $this->getProductService();

    $productService->createFromJson($kitchenSinkJson);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      $this->assertEquals('PHP 3-Tier', $product->name);
      $inputcount = 0;
      foreach($product->resources as $resource) {
        // Not sure how thoroughly I want to test here.
      }
    }
  }

  public function testCanCreateProductFromKitchenSinkJsonString() {
    $str = file_get_contents(__DIR__ . '/../../../../../../json/input/kitchensink.json');

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      $this->assertEquals('PHP 3-Tier', $product->name);
      $inputcount = 0;
      foreach($product->resources as $resource) {
        // Not sure how thoroughly I want to test here.
      }
    }
  }

  /**
   * @group json_to_odm
   */
  public function testCreateProductFromJsonConvertsScalarProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "Deployment",
      "resource_type": "deployment",
      "name": "name",
      "inputs": [ ],
      "servers": [ ],
      "server_arrays": [ ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      foreach($product->resources as $resource) {
        $this->assertEquals("deployment", $resource->resource_type);
        $this->assertEquals("name", $resource->name);
      }
    }
  }

  /**
   * @group json_to_odm
   */
  public function testCreateProductFromJsonConvertsArrayOfScalarProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "ssl_enable_product_input",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false"],
      "input_name": "ssl_enable",
      "display_name": "Enable SSL",
      "description": "Enable SSL for application servers?",
      "advanced": true
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      foreach($product->resources as $resource) {
        $this->assertEquals("select_product_input", $resource->resource_type);
        $this->assertEquals(array("true","false"), $resource->options);
        $this->assertEquals(array("false"), $resource->default_value);
      }
    }
  }

  /**
   * @group json_to_odm
   * @group resource
   */
  public function testCreateProductFromJsonConvertsCloudToResourceHrefProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "datacenter",
      "resource_type": "datacenter_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "default_value": [
        {
          "cloud_href": "/api/clouds/1",
          "resource_hrefs": [
            "/api/clouds/1/datacenters/36F8AT46B08LN",
            "/api/clouds/1/datacenters/6BFHL6M8K8FHH"
          ]
        },
        {
          "cloud_href": "/api/clouds/2",
          "resource_hrefs": [
            "/api/clouds/2/datacenters/2T6TBBRK2E94D",
            "/api/clouds/2/datacenters/83CG48S3I2H31"
          ]
        }
      ],
      "input_name": "datacenter",
      "display_name": "Doesn't Matter",
      "display": false
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      foreach($product->resources as $resource) {
        $this->assertEquals("datacenter_product_input", $resource->resource_type);
        $this->assertEquals(2, count($resource->default_value));
        foreach($resource->default_value as $default_value) {
          $this->assertEquals("SelfService\Document\CloudToResourceHref", get_class($default_value));
          $this->assertEquals(2, count($default_value->resource_hrefs));
        }
      }
    }
  }

  /**
   * @group json_to_odm
   */
  public function testCreateProductFromJsonConvertsSingleReferenceProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "Deployment",
      "resource_type": "deployment",
      "name": { "ref": "text_product_input", "id": "deployment_name" },
      "inputs": [ ],
      "servers": [ ],
      "server_arrays": [ ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      foreach($product->resources as $resource) {
        $this->assertEquals("deployment", $resource->resource_type);
        $this->assertTrue(is_array($resource->name), "Reference was expected to be an array, but it was something else");
        $this->assertArrayHasKey("ref", $resource->name);
        $this->assertArrayHasKey("id", $resource->name);
        $this->assertEquals(2, count($resource->name), "Reference has more than two keys.  Only [ref,id] was expected but got ".join(',',array_keys($resource->name)));
      }
    }
  }

  /**
   * @group json_to_odm
   */
  public function testCreateProductFromJsonConvertsArrayWithSingleNestedResourceProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "Deployment",
      "resource_type": "deployment",
      "name": "name",
      "inputs": [
        {
          "id": "SslEnableInput",
          "resource_type": "input",
          "name": "web_apache\/ssl_enable",
          "value": "text:false"
        }
      ],
      "servers": [ ],
      "server_arrays": [ ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      $resource_count = 0;
      foreach($product->resources as $resource) {
        $resource_count++;
        if($resource->id == "Deployment") {
          $this->assertTrue(is_array($resource->inputs), "Inputs was not an array");
          $input_ref = $resource->inputs[0];
          $this->assertTrue(is_array($input_ref), "Reference was expected to be an array, but it was something else");
          $this->assertArrayHasKey("ref", $input_ref);
          $this->assertArrayHasKey("id", $input_ref);
          $this->assertArrayHasKey("nested", $input_ref);
          $objectvars = array_keys($input_ref);
          $this->assertEquals(3, count($objectvars), "Reference has more than two keys.  Only [ref,id,nested] was expected but got ".join(',',array_keys($objectvars)));
        } else if ($resource->id == "SslEnableInput") {
          $this->assertEquals("SelfService\Document\Input", get_class($resource));
          $objectvars = get_object_vars($resource);
          $this->assertEquals(5, count($objectvars), "Reference has more than two properties.  Only [id,resource_type,name,value,depends] was expected but got ".join(',',array_keys($objectvars)));
        }
      }
      $this->assertEquals(2, $resource_count);
    }
  }

  /**
   * @group json_to_odm
   */
  public function testCreateProductFromJsonConvertsArrayWithManyNestedResourcesProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "Deployment",
      "resource_type": "deployment",
      "name": "name",
      "inputs": [
        {
          "id": "SslEnableInput",
          "resource_type": "input",
          "name": "web_apache\/ssl_enable",
          "value": "text:false"
        },
        {
          "id": "DbFqdnInput",
          "resource_type": "input",
          "name": "db\/dns\/master\/fqdn",
          "value": "text:localhost"
        }
      ],
      "servers": [ ],
      "server_arrays": [ ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      $resource_count = 0;
      foreach($product->resources as $resource) {
        $resource_count++;
        if($resource->id == "Deployment") {
          $this->assertTrue(is_array($resource->inputs), "Inputs was not an array");
          foreach($resource->inputs as $input_ref) {
            $this->assertTrue(is_array($input_ref), "Reference was expected to be an array, but it was something else");
            $this->assertArrayHasKey("ref", $input_ref);
            $this->assertArrayHasKey("id", $input_ref);
            $this->assertArrayHasKey("nested", $input_ref);
            $objectvars = array_keys($input_ref);
            $this->assertEquals(3, count($objectvars), "Reference has more than two keys.  Only [ref,id,nested] was expected but got ".join(',',array_keys($objectvars)));
          }
        } else if ($resource->id == "SslEnableInput") {
          $this->assertEquals("SelfService\Document\Input", get_class($resource));
        } else if ($resource->id == "DbFqdnInput") {
          $this->assertEquals("SelfService\Document\Input", get_class($resource));
        }
      }
      $this->assertEquals(3, $resource_count);
    }
  }

  /**
   * @group json_to_odm
   */
  public function testCreateProductFromJsonConvertsArrayWithMixOfNestedResourceAndReferenceProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "DbFqdnInput",
      "resource_type": "input",
      "name": "db\/dns\/master\/fqdn",
      "value": "text:localhost"
    },
    {
      "id": "Deployment",
      "resource_type": "deployment",
      "name": "name",
      "inputs": [
        {
          "id": "SslEnableInput",
          "resource_type": "input",
          "name": "web_apache\/ssl_enable",
          "value": "text:false"
        },
        { "ref": "input", "id": "DbFqdnInput" }
      ],
      "servers": [ ],
      "server_arrays": [ ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    foreach($products as $product) {
      $resource_count = 0;
      foreach($product->resources as $resource) {
        $resource_count++;
        if($resource->id == "Deployment") {
          $this->assertTrue(is_array($resource->inputs), "Inputs was not an array");
          $input_ref = $resource->inputs[0];
          $this->assertTrue(is_array($input_ref), "Reference was expected to be an array, but it was something else");
          $this->assertArrayHasKey("ref", $input_ref);
          $this->assertArrayHasKey("id", $input_ref);
          $this->assertArrayHasKey("nested", $input_ref);
          $objectvars = array_keys($input_ref);
          $this->assertEquals(3, count($objectvars), "Reference has more than three keys.  Only [ref,id,nested] was expected but got ".join(',',array_keys($objectvars)));
          $input_ref = $resource->inputs[1];
          $this->assertTrue(is_array($input_ref), "Reference was expected to be an array, but it was something else");
          $this->assertArrayHasKey("ref", $input_ref);
          $this->assertArrayHasKey("id", $input_ref);
          $objectvars = array_keys($input_ref);
          $this->assertEquals(2, count($objectvars), "Reference has more than two keys.  Only [ref,id] was expected but got ".join(',',array_keys($objectvars)));
        } else if ($resource->id == "SslEnableInput") {
          $this->assertEquals("SelfService\Document\Input", get_class($resource));
          $objectvars = get_object_vars($resource);
          $this->assertEquals(5, count($objectvars), "Reference has more than two properties.  Only [id,resource_type,name,value,depends] was expected but got ".join(',',array_keys($objectvars)));
        }
      }
      $this->assertEquals(3, $resource_count);
    }
  }

  public function testCanDeleteProductIncludingAllSubordinates() {
    $str = file_get_contents(__DIR__ . '/../../../../../../json/input/kitchensink.json');

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());

    $product = null;
    foreach($products as $prod) {
      $product = $prod;
    }

    $productService->remove($product->id);

    $products = $productService->findAll();
    $this->assertEquals(0, $products->count());
  }

  public function testCanUpdateName() {
    $str = file_get_contents(__DIR__ . '/../../../../../../json/input/kitchensink.json');

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());

    $product = null;
    foreach($products as $prod) {
      $product = $prod;
    }

    $productService->update($product->id, array('name' => 'foobar'));

    $product = $productService->find($product->id);
    $this->assertEquals('foobar', $product->name);
  }

  public function testCanUpdateIcon() {
    $str = file_get_contents(__DIR__ . '/../../../../../../json/input/kitchensink.json');

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());

    $product = null;
    foreach($products as $prod) {
      $product = $prod;
    }

    $productService->update($product->id, array('icon_filename' => 'foobar.png'));

    $product = $productService->find($product->id);
    $this->assertEquals('foobar.png', $product->icon_filename);
  }

  public function testCanUpdateLaunchServers() {
    $str = file_get_contents(__DIR__ . '/../../../../../../json/input/kitchensink.json');

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());

    $product = null;
    foreach($products as $prod) {
      $product = $prod;
    }

    $productService->update($product->id, array('launch_servers' => true));

    $product = $productService->find($product->id);
    $this->assertTrue($product->launch_servers);
  }

  /**
   * @group odm_to_json
   */
  public function testToInputJsonConvertsScalarProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "Deployment",
      "resource_type": "deployment",
      "name": "name",
      "inputs": [ ],
      "servers": [ ],
      "server_arrays": [ ]
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    $product = null;
    foreach($products as $prod) {
      $product = $prod;
    }
    $jsonStr = $productService->toInputJson($product->id);
    $jsonObj = json_decode($jsonStr);
    $this->assertEquals("name", $jsonObj->resources[0]->name);
  }

  /**
   * @group odm_to_json
   */
  public function testToInputJsonConvertsArrayOfScalarProperly() {
    $str = <<<EOF
{
  "id": "518a8f839aec0cc32e000000",
  "version": "1.0.0",
  "name": "PHP 3-Tier",
  "icon_filename": "php.png",
  "launch_servers": true,
  "resources": [
    {
      "id": "ssl_enable_product_input",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false"],
      "input_name": "ssl_enable",
      "display_name": "Enable SSL",
      "description": "Enable SSL for application servers?",
      "advanced": true
    }
  ]
}
EOF;

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());
    $product = null;
    foreach($products as $prod) {
      $product = $prod;
    }
    $jsonStr = $productService->toInputJson($product->id);
    $jsonObj = json_decode($jsonStr);
    $resource = $jsonObj->resources[0];
    $this->assertEquals("select_product_input", $resource->resource_type);
    $this->assertEquals(array("true","false"), $resource->options);
    $this->assertEquals(array("false"), $resource->default_value);
  }

  /**
   * @group odm_to_json
   */
  public function testCanConvertToInputJson() {
    $str = file_get_contents(__DIR__ . '/../../../../../../json/input/kitchensink.json');

    $productService = $this->getProductService();

    $productService->createFromJson($str);

    $products = $productService->findAll();
    $this->assertEquals(1, $products->count());

    $product = null;
    foreach($products as $prod) {
      $product = $prod;
    }

    $jsonStr = $productService->toInputJson($product->id);
    # TODO: How to validate this? The validators for PHP just don't
    # cover all of the declarations I use.
  }

  /**
   * @group odm_to_json
   */
  public function testCanConvertToOutputJson() {
    $path = realpath(__DIR__.'/../../../../../../products/baselinux.json');
    $str = file_get_contents($path);

    $productService = $this->getProductService();

    $product = $productService->createFromJson($str);

    $params = array(
      "deployment_name" => "Base",
      "cloud" => "1",
      "instance_count" => "1",
      "instance_type" => "/api/clouds/1/instance_types/CQQV62T389R32"
    );

    $jsonStr = $productService->toOutputJson($product->id, $params);
    $this->assertTrue(preg_match('/[a-z]+_product_input/', $jsonStr) == 0, "There were input references in the output json, this is a no no");
    # TODO: Could use more validation here too
  }

}