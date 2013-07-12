<?php

namespace SelfServiceTest\Provisioner;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class RsApiProvisionerTest extends AbstractHttpControllerTestCase {

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
   * @return \SelfService\Provisioner\RsApiProvisioner
   */
  protected function getProvisioner() {
    return $this->getApplicationServiceLocator()->get('Provisioner');
  }

  public function testPlaceholder() {
    $this->markTestSkipped("Not sure what to test for the RsApiProvisioner, it mostly consumes other services and classes");
  }

  public function testDoesNotCreateProvisionedObjecRecordForSecurityGroupWhenNoSecurityGroupIsCreated() {
    $prov_helper_mock = $this->getMockBuilder('\SelfService\Service\ProvisioningHelper')
      ->disableOriginalConstructor()
      ->getMock();
    $prov_helper_mock->expects($this->once())
      ->method('provisionSecurityGroup')
      ->will($this->returnValue(false));

    $prov_prod_service_mock = $this->getMock('\SelfService\Service\Entity\ProvisionedProductService');
    $prov_prod_service_mock->expects($this->never())
      ->method('addProvisionedObject');

    $log = $this->getMock('\Zend\Log\Logger');

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('rs_provisioning_helper', $prov_helper_mock);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\ProvisionedProductService', $prov_prod_service_mock);
    $this->getApplicationServiceLocator()->setService('logger', $log);

    $provisioner = $this->getProvisioner();
    $json = <<<EOF
{
   "id":"51df709279165e4b22000000",
   "version":"1.0.0",
   "name":"Base Linux",
   "icon_filename":"redhat.png",
   "launch_servers":true,
   "resources":[
      {
         "resource_type":"security_group",
         "cloud_href":"\/api\/clouds\/1869",
         "name":"base-default",
         "description":"Base Linux",
         "security_group_rules":[
            {
               "resource_type":"security_group_rule",
               "cidr_ips":"0.0.0.0\/0",
               "protocol":"tcp",
               "protocol_details":{
                  "end_port":"22",
                  "start_port":"22"
               },
               "source_type":"cidr_ips",
               "id":"SshIngressSecurityGroupRule"
            },
            {
               "resource_type":"security_group_rule",
               "cidr_ips":"0.0.0.0\/0",
               "protocol":"tcp",
               "protocol_details":{
                  "end_port":"80",
                  "start_port":"80"
               },
               "source_type":"cidr_ips",
               "id":"HttpIngressSecurityGroupRule"
            }
         ],
         "id":"DefaultSecurityGroup"
      }
   ]
}
EOF;
    $provisioner->provision('1', $json);
  }

}