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
}