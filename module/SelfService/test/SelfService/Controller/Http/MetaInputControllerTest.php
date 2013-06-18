<?php

namespace SelfServiceTest\Controller\Http;

use Zend\Http\Request as HttpRequest;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class MetaInputControllerTest extends AbstractHttpControllerTestCase {

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();
  }

  public function testInstanceTypesActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $instance_types = array();
    $type1 = new \stdClass();
    $type1->href = '/api/clouds/1/instance_types/ABC123';
    $type1->name = 'm1.small';
    $instance_types[] = $type1;

    $cachemock = $this->getMockBuilder('\SelfService\Service\RightScaleAPICache')
      ->disableOriginalConstructor()
      ->getMock();
    $cachemock->expects($this->once())
      ->method('getInstanceTypes')
      ->will($this->returnValue($instance_types));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $cachemock);

    $this->dispatch('/metainput/instancetypes',
      HttpRequest::METHOD_POST, array('cloud_href' => '/api/clouds/1'));

    $this->assertActionName('instancetypes');
    $this->assertControllerName('selfservice\controller\metainput');
    $this->assertResponseStatusCode(200);
  }

  public function testInstanceTypesActionReturnsJson() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $instance_types = array();
    $type1 = new \stdClass();
    $type1->href = '/api/cloud/1/instance_types/ABC123';
    $type1->name = 'm1.small';
    $instance_types[] = $type1;

    $cachemock = $this->getMockBuilder('\SelfService\Service\RightScaleAPICache')
      ->disableOriginalConstructor()
      ->getMock();
    $cachemock->expects($this->once())
      ->method('getInstanceTypes')
      ->will($this->returnValue($instance_types));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $cachemock);

    $this->dispatch('/metainput/instancetypes',
          HttpRequest::METHOD_POST, array('cloud_href' => '/api/clouds/1'));

    $response = strval($this->getResponse());

    $this->assertActionName('instancetypes');
    $this->assertControllerName('selfservice\controller\metainput');
    $this->assertResponseStatusCode(200);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testInstanceTypesActionPassesThroughInstanceTypeIdsIfProvided() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $instance_types = array();
    $type1 = new \stdClass();
    $type1->href = '/api/cloud/1/instance_types/ABC123';
    $type1->name = 'm1.small';
    $instance_types[] = $type1;

    $cachemock = $this->getMockBuilder('\SelfService\Service\RightScaleAPICache')
      ->disableOriginalConstructor()
      ->getMock();
    $cachemock->expects($this->once())
      ->method('getInstanceTypes')
      ->will($this->returnValue($instance_types));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $cachemock);

    $this->dispatch('/metainput/instancetypes',
      HttpRequest::METHOD_POST, array('instance_type_ids' => array('1', '2'), 'cloud_href' => '/api/clouds/1'));

    $response = strval($this->getResponse());

    $this->assertActionName('instancetypes');
    $this->assertControllerName('selfservice\controller\metainput');
    $this->assertResponseStatusCode(200);
    $this->assertContains('"instance_type_ids":["1","2"]', $response);
  }

  public function testDatacentersActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $datacenters = array();
    $dc1 = new \stdClass();
    $dc1->href = '/api/cloud/1/datacenters/ABC123';
    $dc1->name = 'dc1';
    $datacenters[] = $dc1;

    $cachemock = $this->getMockBuilder('\SelfService\Service\RightScaleAPICache')
      ->disableOriginalConstructor()
      ->getMock();
    $cachemock->expects($this->once())
      ->method('getDatacenters')
      ->will($this->returnValue($datacenters));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $cachemock);

    $this->dispatch('/metainput/datacenters',
          HttpRequest::METHOD_POST, array('cloud_href' => '/api/clouds/1'));

    $this->assertActionName('datacenters');
    $this->assertControllerName('selfservice\controller\metainput');
    $this->assertResponseStatusCode(200);
  }

  public function testDatacentersActionReturnsJson() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $datacenters = array();
    $dc1 = new \stdClass();
    $dc1->href = '/api/cloud/1/datacenters/ABC123';
    $dc1->name = 'dc1';
    $datacenters[] = $dc1;

    $cachemock = $this->getMockBuilder('\SelfService\Service\RightScaleAPICache')
      ->disableOriginalConstructor()
      ->getMock();
    $cachemock->expects($this->once())
      ->method('getDatacenters')
      ->will($this->returnValue($datacenters));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $cachemock);

    $this->dispatch('/metainput/datacenters',
          HttpRequest::METHOD_POST, array('cloud_href' => '/api/clouds/1'));

    $response = strval($this->getResponse());

    $this->assertActionName('datacenters');
    $this->assertControllerName('selfservice\controller\metainput');
    $this->assertResponseStatusCode(200);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testDatacentersActionPassesThroughDatacenterIdsIfProvided() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $datacenters = array();
    $dc1 = new \stdClass();
    $dc1->href = '/api/cloud/1/datacenters/ABC123';
    $dc1->name = 'dc1';
    $datacenters[] = $dc1;

    $cachemock = $this->getMockBuilder('\SelfService\Service\RightScaleAPICache')
      ->disableOriginalConstructor()
      ->getMock();
    $cachemock->expects($this->once())
      ->method('getDatacenters')
      ->will($this->returnValue($datacenters));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('RightScaleAPICache', $cachemock);

    $this->dispatch('/metainput/datacenters',
      HttpRequest::METHOD_POST, array('datacenter_ids' => array('1', '2'),'cloud_href' => '/api/clouds/1'));

    $response = strval($this->getResponse());

    $this->assertActionName('datacenters');
    $this->assertControllerName('selfservice\controller\metainput');
    $this->assertResponseStatusCode(200);
    $this->assertContains('"datacenter_ids":["1","2"]', $response);
  }
}