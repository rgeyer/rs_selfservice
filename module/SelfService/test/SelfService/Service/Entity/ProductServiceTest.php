<?php

namespace SelfServiceTest\Service;

use SelfService\Entity\Provisionable\Product;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ProductServiceTest extends AbstractHttpControllerTestCase {

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

  protected function assertIsReference($jsonObj, $ref) {
    $this->assertInstanceOf('stdClass', $ref);
    $this->assertTrue(property_exists($ref, 'rel'),
      "reference did not have a rel property");
    $this->assertTrue(property_exists($ref, 'id'),
      "reference did not have an id property");
    $this->assertTrue(property_exists($jsonObj->{$ref->rel}, $ref->id),
      "reference links to a $ref->rel with id $ref->id which does not exist");
  }

  public function testCanCreateProductFromRideJson() {
    $ridepayload = <<<EOF
[{"type":"Deployment","nickname":"lj"},{"type":"Server","publication_id":"46554","revision":"102","name":"DB_MYSQL55_13_2_1","st_name":"Database Manager for MySQL 5.5 (v13.2.1)","inputs":{"sys_dns/choice":"text:DNSMadeEasy","sys_dns/password":"text:password","sys_dns/user":"text:user","db/backup/lineage":"text:changeme"},"info":{"nickname":"Database Manager for MySQL 5.5 (v13.2.1) #1"},"allowOverride":["sys_dns/password","sys_dns/user"]}]
EOF;

    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');

    $productService->createFromRideJson($ridepayload);

    $products = $productService->findAll();
    $this->assertEquals(1, count($products));
    $this->assertEquals('lj', $products[0]->name);
    # Two "default" inputs (cloud and instance type), and the two overrides defined in the $ridepayload above
    $this->assertEquals(4, count($products[0]->meta_inputs));
  }

  public function testCanDeleteProductIncludingAllSubordinates() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');

    $productService->remove(1);

    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\AlertSpec')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\AlertSubjectBase')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\Product')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\SecurityGroup')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\SecurityGroupRule')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\Server')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\ServerArray')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\ServerTemplate')->findAll()));
    $this->assertEquals(0, count($em->getRepository('SelfService\Entity\Provisionable\MetaInputs\ProductMetaInputBase')->findAll()));
  }

  public function testCanUpdateName() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    $productService->update(1, array('name' => 'foobar'));

    $product = $productService->find(1);
    $this->assertEquals('foobar', $product->name);
  }

  public function testCanUpdateIcon() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    $productService->update(1, array('icon_filename' => 'foobar.png'));

    $product = $productService->find(1);
    $this->assertEquals('foobar.png', $product->icon_filename);
  }

  public function testCanUpdateLaunchServers() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    $productService->update(1, array('launch_servers' => true));

    $product = $productService->find(1);
    $this->assertTrue($product->launch_servers);
  }

  public function testCanConvertToJson() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');

    \SelfService\Product\php3tier::add($em);

    $productService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\ProductService');
    $jsonStr = $productService->toJson(1);
    print $jsonStr;
    $jsonObj = json_decode($jsonStr);
    $this->assertTrue(property_exists($jsonObj, 'name'), "Product JSON did not include product name");
    $this->assertTrue(property_exists($jsonObj, 'icon_filename'), "Product JSON did not include product icon_filename");
    $this->assertTrue(property_exists($jsonObj, 'security_groups'), "Product JSON did not include security_groups");
    $securityGroups = get_object_vars($jsonObj->security_groups);
    $this->assertGreaterThan(0, count($securityGroups));
    foreach($securityGroups as $propname => $propval) {
      $this->assertRegExp(',[0-9]*,',strval($propname));
      $this->assertTrue(property_exists($propval, 'name'), "Security Group JSON did not include name");
      $this->assertTrue(property_exists($propval, 'rules'), "Security Group JSON did not include rules");
      $this->assertGreaterThan(0, count($propval->rules), "Security Group $propname has no rules");
      foreach($propval->rules as $rule_idx => $rule) {
        $this->assertTrue(property_exists($rule, 'ingress_protocol'), "Security Group Rule $rule_idx for Security Group $propname did not include ingress_protocol");
        $this->assertTrue(property_exists($rule, 'ingress_from_port'), "Security Group Rule $rule_idx for Security Group $propname did not include ingress_from_port");
        $this->assertTrue(property_exists($rule, 'ingress_to_port'), "Security Group Rule $rule_idx for Security Group $propname did not include ingress_to_port");
        $group_or_cidr = property_exists($rule, 'ingress_group') | property_exists($rule, 'ingress_cidr_ips');
        $this->assertGreaterThan(
          0,
          count($group_or_cidr),
          "Security Group Rule $rule_idx for Security Group $propname did not include an ingress group or cidr"
        );
        if(property_exists($rule, 'ingress_group')) {
          $this->assertIsReference($jsonObj, $rule->ingress_group);
          # TODO: This assumes all clouds require owners, which is not always the case. I.E. Google
          $this->assertTrue(property_exists($rule, 'ingress_owner'), "Security Group Rule $rule_idx for Security Group $propname specified an ingress_group, but no ingress_owner");
        }
      }
      $this->assertTrue(property_exists($propval, 'cloud_id'), "Security Group JSON did not include cloud_id");
    }
    $this->assertTrue(property_exists($jsonObj, 'servers'), "Product JSON did not include servers");
    $servers = get_object_vars($jsonObj->servers);
    $this->assertGreaterThan(0, count($servers));
    foreach($servers as $propname => $propval) {
      $this->assertTrue(property_exists($propval, 'nickname'), "Server did not include nickname");
      $this->assertTrue(property_exists($propval, 'server_template'), "Server did not include server_template");
      $this->assertIsReference($jsonObj, $propval->server_template);
      $this->assertTrue(property_exists($propval, 'count'), "Server did not include count");
      $this->assertTrue(property_exists($propval, 'cloud_id'), "Server did not include cloud_id");
      $this->assertIsReference($jsonObj, $propval->cloud_id);
      $this->assertTrue(property_exists($propval, 'security_groups'), "Server did not include security_groups");
      foreach($propval->security_groups as $secgrpref) {
        $this->assertIsReference($jsonObj, $secgrpref);
      }
    }
    $this->assertTrue(property_exists($jsonObj, 'arrays'), "Product JSON did not include arrays");
    $arrays = get_object_vars($jsonObj->arrays);
    $this->assertGreaterThan(0, count($arrays));
    foreach($arrays as $propname => $propval) {
      $this->assertTrue(property_exists($propval, 'nickname'), "Array did not include nickname");
      $this->assertTrue(property_exists($propval, 'server_template'), "Array did not include server_template");
      $this->assertIsReference($jsonObj, $propval->server_template);
      $this->assertTrue(property_exists($propval, 'min_count'), "Array did not include count");
      $this->assertTrue(property_exists($propval, 'max_count'), "Array did not include count");
      $this->assertTrue(property_exists($propval, 'cloud_id'), "Array did not include cloud_id");
      $this->assertIsReference($jsonObj, $propval->cloud_id);
      $this->assertTrue(property_exists($propval, 'security_groups'), "Array did not include security_groups");
      foreach($propval->security_groups as $secgrpref) {
        $this->assertIsReference($jsonObj, $secgrpref);
      }
      $this->assertTrue(property_exists($propval, 'type'), "Array did not include type");
      $this->assertTrue(property_exists($propval, 'tag'), "Array did not include tag");
    }
    $this->assertTrue(property_exists($jsonObj, 'alerts'), "Product JSON did not include alerts");
    $alerts = get_object_vars($jsonObj->alerts);
    $this->assertGreaterThan(0, count($alerts));
    foreach($alerts as $propname => $propval) {
      $this->assertTrue(property_exists($propval, 'name'), "Alert did not include name property");
      $this->assertTrue(property_exists($propval, 'file'), "Alert did not include file property");
      $this->assertTrue(property_exists($propval, 'variable'), "Alert did not include variable property");
      $this->assertTrue(property_exists($propval, 'cond'), "Alert did not include cond property");
      $this->assertTrue(property_exists($propval, 'threshold'), "Alert did not include threshold property");
      $this->assertTrue(property_exists($propval, 'duration'), "Alert did not include duration property");
      $this->assertTrue(property_exists($propval, 'action'), "Alert did not include action property");
      $this->assertTrue(property_exists($propval, 'vote_tag'), "Alert did not include vote_tag property");
      $this->assertTrue(property_exists($propval, 'vote_type'), "Alert did not include vote_type property");
      $this->assertTrue(property_exists($propval, 'subjects'), "Alert did not include subjects property");
      foreach($propval->subjects as $subject) {
        $this->assertIsReference($jsonObj, $subject);
      }
    }
    $this->assertTrue(property_exists($jsonObj, 'meta_inputs'), "Product JSON did not include meta_inputs");
    $meta_inputs = get_object_vars($jsonObj->meta_inputs);
    $this->assertGreaterThan(0, count($meta_inputs));
    foreach($meta_inputs as $propname => $propval) {
      $this->assertTrue(property_exists($propval, 'type'), "Meta Input JSON did not include type property");
      $this->assertTrue(property_exists($propval, 'value'), "Meta Input JSON did not include value property");
      $this->assertTrue(property_exists($propval, 'extra'), "Meta Input JSON did not include extra property");
      $this->assertTrue(property_exists($propval->extra, 'input_name'), "Meta Input (Extra) JSON did not include input_name property");
      $this->assertTrue(property_exists($propval->extra, 'display_name'), "Meta Input (Extra) JSON did not include display_name property");
      $this->assertTrue(property_exists($propval->extra, 'description'), "Meta Input (Extra) JSON did not include description property");
      $this->assertTrue(property_exists($propval->extra, 'default_value'), "Meta Input (Extra) JSON did not include default_value property");
    }
    $this->assertTrue(property_exists($jsonObj, 'launch_servers'), "Product JSON did not include launch_servers");
    $this->assertTrue(property_exists($jsonObj, 'parameters'), "Product JSON did not include parameters");
    $this->assertGreaterThan(0, count($jsonObj->parameters));
    foreach($jsonObj->parameters as $propname => $propval) {
      $this->assertTrue(property_exists($propval, 'name'), "Parameter JSON did not include name property");
      $this->assertTrue(property_exists($propval, 'value'), "Parameter JSON did not include value property");
      $this->assertTrue(property_exists($propval, 'extra'), "Parameter JSON did not include extra property");
      $this->assertTrue(property_exists($propval->extra, 'input_name'), "Parameter (Extra) JSON did not include input_name property");
      $this->assertTrue(property_exists($propval->extra, 'display_name'), "Parameter (Extra) JSON did not include display_name property");
      $this->assertTrue(property_exists($propval->extra, 'description'), "Parameter (Extra) JSON did not include description property");
      $this->assertTrue(property_exists($propval->extra, 'default_value'), "Parameter (Extra) JSON did not include default_value property");
      $this->assertTrue(property_exists($propval->extra, 'rs_input_name'), "Parameter (Extra) JSON did not include rs_input_name property");
    }
    $this->assertTrue(property_exists($jsonObj, 'server_templates'), "Product JSON did not include server_templates");
    $server_templates = get_object_vars($jsonObj->server_templates);
    $this->assertGreaterThan(0, count($server_templates));
    foreach($server_templates as $propname => $propval) {
      $this->assertTrue(property_exists($propval, 'nickname'), "Server Template JSON did not include nickname property");
      $this->assertTrue(property_exists($propval, 'version'), "Server Template JSON did not include version property");
      $this->assertTrue(property_exists($propval, 'publication_id'), "Server Template JSON did not include publication_id property");
    }
  }

}