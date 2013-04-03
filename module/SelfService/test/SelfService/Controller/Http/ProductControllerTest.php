<?php

namespace SelfServiceTest\Controller\Http;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductControllerTest extends AbstractHttpControllerTestCase {

  protected function mockProvisioningHelper() {
    return $this->getMockBuilder('SelfService\Service\ProvisioningHelper')
      ->disableOriginalConstructor()
      ->getMock();
  }

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();

    $serviceManager = $this->getApplicationServiceLocator();

    // Initialize the schema.. Maybe I should register a module for clearing the schema/data
    // and/or loading mock test data
    $em = $serviceManager->get('doctrine.entitymanager.orm_default');
    $cli = new \Symfony\Component\Console\Application("PHPUnit Bootstrap", 1);
    $cli->setAutoExit(false);
    $helperSet = $cli->getHelperSet();
    $helperSet->set(new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em), 'em');
    $cli->addCommands(array(new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand()));
    $cli->run(
      new \Symfony\Component\Console\Input\ArrayInput(array('orm:schema-tool:create')),
      new \Symfony\Component\Console\Output\NullOutput()
    );
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
    $sl = $this->getApplicationServiceLocator();
    $sl->setAllowOverride(true);
    $sl->setService('rs_provisioning_helper', $this->mockProvisioningHelper());
    $this->dispatch('/product/provision/1');

    $response = strval($this->getResponse());

    $this->assertActionName('provision');
    $this->assertControllerName('selfservice\controller\product');
    $this->assertResponseStatusCode(200);
    $this->assertContains('{"result":"success"', $response);
    $this->assertContains('content-type: application/json;', $response);
  }

  public function testRideImportActionAcceptsPostAndReturnsJson() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());

    $ridepayload = '[{"type":"Server","publication_id":"46546","revision":"136","name":"LB_HAPROXY_13_2_1","st_name":"Load Balancer with HAProxy (v13.2.1)","inputs":{"lb/service/provider":"text:lb_client"},"info":{"ec2_security_group_href":"https://my.rightscale.com/api/acct/71/ec2_security_groups/234123","ec2_ssh_key_href":"https://my.rightscale.com/api/acct/71/ec2_ssh_keys/274173","cloud_id":"1","nickname":"Load Balancer with HAProxy (v13.2.1) #1","server_template_href":"https://my.rightscale.com/api/acct/71/server_templates/275034001"}},{"type":"Server","publication_id":"46554","revision":"102","name":"DB_MYSQL55_13_2_1","st_name":"Database Manager for MySQL 5.5 (v13.2.1)","inputs":{"db/backup/lineage":"text:changeme","sys_dns/choice":"text:DNSMadeEasy","sys_dns/password":"text:password","sys_dns/user":"text:user"},"info":{"ec2_security_group_href":"https://my.rightscale.com/api/acct/71/ec2_security_groups/234123","ec2_ssh_key_href":"https://my.rightscale.com/api/acct/71/ec2_ssh_keys/274173","cloud_id":"1","nickname":"Database Manager for MySQL 5.5 (v13.2.1) #1","server_template_href":"https://my.rightscale.com/api/acct/71/server_templates/275034001"}}]';
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
}