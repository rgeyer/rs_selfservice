<?php

namespace SelfServiceTest\Controller\Http;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductControllerTest extends AbstractHttpControllerTestCase {

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
  protected function getProductEntityService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }

  public function testIndexActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/product/index');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
  }

  public function testProvisionActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $product = $this->getProductEntityService()->createFromJson(array('nickname' => "foo"));
    $this->dispatch('/product/provision/'.$product->id);

    $response = strval($this->getResponse());

    $this->assertActionName('provision');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testRideImportActionAcceptsPostAndReturnsJson() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $ridepayload = '[{"type":"Deployment","nickname":"lj"},{"type":"Server","publication_id":"46554","revision":"102","name":"DB_MYSQL55_13_2_1","st_name":"Database Manager for MySQL 5.5 (v13.2.1)","inputs":{"sys_dns/choice":"text:DNSMadeEasy","sys_dns/password":"text:password","sys_dns/user":"text:user","db/backup/lineage":"text:changeme"},"info":{"nickname":"Database Manager for MySQL 5.5 (v13.2.1) #1"},"allowOverride":["sys_dns/password","sys_dns/user"]}]';
    $this->dispatch('/product/rideimport', 'POST', array('dep' => $ridepayload));

    $response = strval($this->getResponse());

    $this->assertActionName('rideimport');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testRideImportActionAcceptsGetAndReturnsForm() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/product/rideimport');

    $response = strval($this->getResponse());

    $this->assertActionName('rideimport');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//form', 1);
  }

  public function testCanAccessEditAction() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $product = $this->getProductEntityService()->createFromJson(array('nickname' => "foo"));
    $this->dispatch('/product/edit/'.$product->id);

    $response = strval($this->getResponse());

    $this->assertActionName('edit');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//form', 1);
  }

  public function testEditActionListsIcons() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $product = $this->getProductEntityService()->createFromJson(array('nickname' => "foo"));
    $this->dispatch('/product/edit/'.$product->id);

    $response = strval($this->getResponse());

    $this->assertActionName('edit');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCountMin('//select[id="icon_filename"]/option', 1);
  }

  public function testCanAccessUpdateAction() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $product = $this->getProductEntityService()->createFromJson(array('nickname' => "foo"));
    $this->dispatch('/product/update/'.$product->id);

    $response = strval($this->getResponse());

    $this->assertActionName('update');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testRenderMetaFormCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $product = $this->getProductEntityService()->createFromJson(array('nickname' => "foo"));
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $response = strval($this->getResponse());

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
  }

  public function testRenderMetaFormContainsForm() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $product = $this->getProductEntityService()->createFromJson(array('nickname' => "foo"));
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//form', 1);
  }

  public function testRenderMetaFormActionIsCorrect() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $product = $this->getProductEntityService()->createFromJson(array('nickname' => "foo"));
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//form[@action="/product/provision/'.$product->id.'"]', 1);
  }

  public function testRenderMetaFormRendersOneInputInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "text_product_input",
      "default_value": "foo",
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/input[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/input[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/input[@value="foo"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/label[@for="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/label["display name"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/input[@id="fooinput"]', 0);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/input[@name="fooinput"]', 0);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/input[@value="foo"]', 0);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/label[@for="fooinput"]', 0);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/label["display name"]', 0);
  }

  public function testRenderMetaFormRendersOneInputInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "text_product_input",
      "default_value": "foo",
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/input[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/input[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/input[@value="foo"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/label[@for="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/label["display name"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/input[@id="fooinput"]', 0);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/input[@name="fooinput"]', 0);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/input[@value="foo"]', 0);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/label[@for="fooinput"]', 0);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/label["display name"]', 0);
  }

  public function testRenderMetaFormRendersInstanceTypeInputInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "name";
    $cloud->id = "id";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description"
    },
    {
      "id": "fooinput",
      "resource_type": "instance_type_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option', 0);
  }

  public function testRenderMetaFormRendersInstanceTypeInputWithDefaultInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "name";
    $cloud->id = "id";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description"
    },
    {
      "id": "fooinput",
      "resource_type": "instance_type_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "default_value": [
        {
          "cloud_href": "/api/clouds/1",
          "resource_hrefs": ["/api/clouds/1/instance_types/CQQV62T389R32"]
        },
        {
          "cloud_href": "/api/clouds/2",
          "resource_hrefs": ["/api/clouds/2/instance_types/F1KMCJ2VTC975"]
        }
      ],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@data-defaults]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option', 0);
    $this->assertRegExp("/data-defaults='\[.+\]'/", strval($this->getResponse()));
  }

  public function testRenderMetaFormRendersInstanceTypeInputInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "name";
    $cloud->id = "id";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "foo",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    },
    {
      "id": "fooinput",
      "resource_type": "instance_type_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloudinput" },
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option', 0);
  }

  public function testRenderMetaFormRendersInstanceTypeInputWithDefaultInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "name";
    $cloud->id = "id";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    },
    {
      "id": "fooinput",
      "resource_type": "instance_type_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "default_value": [
        {
          "cloud_href": "/api/clouds/1",
          "resource_hrefs": ["/api/clouds/1/instance_types/CQQV62T389R32"]
        },
        {
          "cloud_href": "/api/clouds/2",
          "resource_hrefs": ["/api/clouds/2/instance_types/F1KMCJ2VTC975"]
        }
      ],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@data-defaults]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option', 0);
    $this->assertRegExp("/data-defaults='\[.+\]'/", strval($this->getResponse()));}

  public function testRenderMetaFormRendersCloudInputInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "cloud_product_input",
      "default_value": "foo",
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput"]', 1);
  }

  public function testRenderMetaFormRendersCloudInputWithDefaultInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "AWS US-East 1";
    $cloud->id = "1";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option[@selected]', 1);
  }

  public function testRenderMetaFormRendersCloudInputInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "cloud_product_input",
      "default_value": "foo",
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput"]', 1);
  }

  public function testRenderMetaFormRendersCloudInputWithDefaultInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "AWS US-East 1";
    $cloud->id = "1";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "fooinput",
      "display_name": "display name",
      "advanced": true,
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option[@selected]', 1);
  }

  public function testRenderMetaFormRendersSelectInputInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false"],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option', 2);
  }

  public function testRenderMetaFormRendersSelectInputWithDefaultInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false","true"],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option', 2);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option[@selected]', 2);
  }

  public function testRenderMetaFormRendersSelectInputWithMultiSelectInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false"],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "multiselect": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@multiple]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option', 2);
  }

  public function testRenderMetaFormRendersSelectInputInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false"],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option', 2);
  }

  public function testRenderMetaFormRendersSelectInputWithDefaultInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false","true"],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option', 2);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option[@selected]', 2);
  }

  public function testRenderMetaFormRendersSelectInputWithMultiSelectInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "select_product_input",
      "options": ["true", "false"],
      "default_value": ["false"],
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true,
      "multiselect": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@multiple]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option', 2);
  }

  public function testRenderMetaFormRendersDatacenterInputInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "name";
    $cloud->id = "id";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description"
    },
    {
      "id": "fooinput",
      "resource_type": "datacenter_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description"
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option', 0);
  }

  public function testRenderMetaFormRendersDatacenterInputWithMultiSelectInBasic() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description"
    },
    {
      "id": "fooinput",
      "resource_type": "datacenter_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "multiselect": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@multiple]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="basic"]/*/select[@id="fooinput"]/option', 0);
  }

  public function testRenderMetaFormRendersDatacenterInputInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();

    $cloud = new \stdClass();
    $cloud->name = "name";
    $cloud->id = "id";

    $apicacheadapter->expects($this->once())
      ->method('getClouds')
      ->will($this->returnValue(array($cloud)));
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    },
    {
      "id": "fooinput",
      "resource_type": "datacenter_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option', 0);
  }

  public function testRenderMetaFormRendersDatacenterInputWithMultiSelectInAdvanced() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "cloudinput",
      "resource_type": "cloud_product_input",
      "default_value": "/api/clouds/1",
      "input_name": "cloudinput",
      "display_name": "display name",
      "description": "description"
    },
    {
      "id": "fooinput",
      "resource_type": "datacenter_product_input",
      "cloud_product_input": { "ref": "cloud_product_input", "id": "cloud" },
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "multiselect": true,
      "advanced": true
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@multiple]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@name="fooinput[]"]', 1);
    $this->assertXpathQueryCount('//div[@id="advanced"]/*/select[@id="fooinput"]/option', 0);
  }

  public function testRenderMetaFormExcludesHiddenInputs() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $apicacheadapter = $this->getMockBuilder("SelfService\Service\RightScaleAPICache")
      ->disableOriginalConstructor()
      ->getMock();
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService("RightScaleAPICache", $apicacheadapter);

    $json = <<<EOF
{
  "name": "foo",
  "resources": [
    {
      "id": "fooinput",
      "resource_type": "text_product_input",
      "default_value": "foo",
      "input_name": "fooinput",
      "display_name": "display name",
      "description": "description",
      "display": false
    }
  ]
}
EOF;

    $product = $this->getProductEntityService()->createFromJson($json);
    $this->dispatch('/product/rendermetaform/'.$product->id);

    $this->assertActionName('rendermetaform');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount('//fieldset[@style]', 1);
    $this->assertContains('<fieldset style="display: none;">', strval($this->getResponse()));
  }
}