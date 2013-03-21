<?php
/*
Copyright (c) 2013 Ryan J. Geyer <me@ryangeyer.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SelfService\Controller;

use Zend\Console\Request as ConsoleRequest;
use SelfService\Entity\Provisionable\Server as ProvisionableServer;
use SelfService\Entity\Provisionable\ServerArray as ProvisionableServerArray;
use SelfService\Entity\Provisionable\Product;
use SelfService\Entity\Provisionable\AlertSpec;
use SelfService\Entity\Provisionable\SecurityGroup;
use SelfService\Entity\Provisionable\ServerTemplate;
use SelfService\Entity\Provisionable\SecurityGroupRule;
use SelfService\Zend\Log\Writer\Collection as CollectionWriter;
use SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\CloudProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\NumberProductMetaInput;


use DateTime;
use Zend\View\Model\JsonModel;
use RGeyer\Guzzle\Rs\Model\Mc\SshKey;
use RGeyer\Guzzle\Rs\Model\Mc\Server;
use RGeyer\Guzzle\Rs\Model\Mc\ServerArray as ApiServerArray;
use SelfService\Entity\ProvisionedSshKey;
use SelfService\Entity\ProvisionedServer;
use SelfService\Entity\ProvisionedProduct;
use SelfService\Entity\ProvisionedDeployment;
use Zend\Authentication\AuthenticationService;
use SelfService\Entity\ProvisionedArray;
use SelfService\Entity\ProvisionedSecurityGroup;

/**
 * ProductController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class ProductController extends BaseController {

  public function rendermetaformAction() {
    $client = $this->getServiceLocator()->get('RightScaleAPIClient');
    $id = $this->params('id');
    $product = $this->getEntityManager()->getRepository('SelfService\Entity\Provisionable\Product')->find($id);
    $cloud_obj = $client->newModel('Cloud');
    $clouds = array();

    foreach($cloud_obj->index() as $cloud) {
      $clouds[$cloud->name] = $cloud->id;
    }

    return array('clouds' => $clouds, 'meta_inputs' => $product->meta_inputs, 'id' => $id, 'use_layout' => false);
  }

  public function provisionAction() {
    $response = array('result' => 'success');
    $now = time();
    $product_id = $this->params('id');
    if(isset($product_id)) {
      $em = $this->getEntityManager();
      $prov_helper = $this->getServiceLocator()->get('rs_provisioning_helper');
      $product = $em->getRepository('SelfService\Entity\Provisionable\Product')->find($product_id);
      if(count($product) == 1) {
        $authSvc = new AuthenticationService();

        $product->mergeMetaInputs($this->params()->fromPost());
        $prov_prod = new ProvisionedProduct();
        $prov_prod->createdate = new DateTime();
        $prov_prod->owner = $em->getRepository('SelfService\Entity\User')->find($authSvc->getIdentity()->id);
        $prov_prod->product = $product;
        # TODO: Potentially failing calls without a try/catch!
        $em->persist($prov_prod);
        $em->flush();

        $response['url'] = $this->url()->fromRoute('admin/provisionedproducts', array('action' => 'show', 'id' => $prov_prod->id));
        $prov_helper->setTags(array('rsss:provisioned_product_id='.$prov_prod->id));
        try {
          # Provision and record deployment
          $deplname = sprintf("rsss-%s-%s", $product->name, $now);
          $depl_params = array(
            'deployment[name]' => $this->params('deployment_name', $deplname),
            'deployment[description]' => sprintf("Created by rs_selfservice for the '%s' product", $product->name)
          );
          $depl = $prov_helper->provisionDeployment($depl_params);
          $prov_depl = new ProvisionedDeployment($depl);
          $prov_prod->provisioned_objects[] = $prov_depl;
          $this->getLogger()->info(sprintf("Created Deployment - Name: %s href: %s", $deplname, $depl->href));

          # Provision and record security groups
          $this->getLogger()->info(sprintf("About to provision %d Security Groups", count($product->security_groups)));
          foreach($product->security_groups as $security_group) {
            $secGrpBaseName = $security_group->name->getVal();
            $secGrpPrefixedName = sprintf("rsss-%s-%s", $secGrpBaseName, $now);
            $security_group->name->setVal($secGrpPrefixedName);
            $secGrp = $prov_helper->provisionSecurityGroup($security_group);
            if($secGrp) {
              $prov_grp = new ProvisionedSecurityGroup($secGrp);
              $prov_prod->provisioned_objects[] = $prov_grp;
            }
          }

          # Provision and record security group rules
          $this->getLogger()->info("About to provision Security Group Rules");
          foreach($product->security_groups as $security_group) {
            $prov_helper->provisionSecurityGroupRules($security_group);
          }

          # Provision and record servers (and ssh keys as a byproduct)
          $this->getLogger()->info(sprintf("About to provision %d different types of servers", count($product->servers)));
          foreach($product->servers as $server) {
            foreach($prov_helper->provisionServer($server, $depl) as $provisioned_model) {
              # TODO: Shouldn't have to differentiate between the return types here, bad code smell.
              if($provisioned_model instanceof SshKey) {
                $prov_key = new ProvisionedSshKey($provisioned_model);
                $prov_prod->provisioned_objects[] = $prov_key;
              }
              if($provisioned_model instanceof Server ) {
                $prov_svr = new ProvisionedServer($provisioned_model);
                $prov_prod->provisioned_objects[] = $prov_svr;
              }
            }
          }

          # Provision and record server arrays (and ssh keys as a byproduct)
          $this->getLogger()->info(sprintf("About to provision %d server arrays", count($product->arrays)));
          foreach($product->arrays as $array) {
            foreach($prov_helper->provisionServerArray($array, $depl) as $provisioned_model) {
              # TODO: Shouldn't have to differentiate between the return types here, bad code smell.
              if($provisioned_model instanceof SshKey) {
                $prov_key = new ProvisionedSshKey($provisioned_model);
                $prov_prod->provisioned_objects[] = $prov_key;
              }
              if($provisioned_model instanceof ApiServerArray ) {
                $prov_ary = new ProvisionedArray($provisioned_model);
                $prov_prod->provisioned_objects[] = $prov_ary;
              }
            }
          }

          # Start yer engines
          if($product->launch_servers) {
            $prov_helper->launchServers();
          }
        } catch (\Exception $e) {
          $response['result'] = 'error';
          $response['error'] = $e->getMessage();
          $this->getLogger()->err("An error occurred provisioning the product. Error: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
        }

        # All provisioned, time to store all the provisioned hrefs in the DB
				try {
					$this->getEntityManager()->persist($prov_prod);
					$this->getEntityManager()->flush();
				} catch (\Exception $e) {
					$response['result'] = 'error';
					$response['error'] = $e->getMessage();
					$this->getLogger()->err("An error occurred persisting the provisioned product to the DB. Error " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
				}
      }
    } else {
      $response['result'] = 'error';
      $response['error'] = 'A product with id ' . $product_id . ' was not found';
      $this->getLogger()->err($response['error']);
    }

    return new JsonModel($response);
	}
	
	public function consoleaddAction() {
    $request = $this->getRequest();

    // Make sure that we are running in a console and the user has not tricked our
    // application into running this action from a public web server.
    if (!$request instanceof ConsoleRequest) {
        throw new \RuntimeException('You can only use this action from a console!');
    }
    $params = $this->getRequest()->getParams()->toArray();
    $collection_writer = new CollectionWriter();
    $this->getLogger()->addWriter($collection_writer);

    $em = $this->getEntityManager();
    $product = new Product();
    try {
      switch ($params['name']) {
        case "baselinux":
          // START Count MetaInput
          $count_metainput = new NumberProductMetaInput();
          $count_metainput->default_value = 1;
          $count_metainput->input_name = 'instance_count';
          $count_metainput->display_name = 'Count';
          $count_metainput->description = 'The number of instances to create and launch';

          $em->persist($count_metainput);
          // END Count MetaInput

          // START Cloud ProductMetaInput
          $cloud_metainput = new CloudProductMetaInput();
          $cloud_metainput->default_value = 1;
          $cloud_metainput->input_name = 'cloud';
          $cloud_metainput->display_name = 'Cloud';
          $cloud_metainput->description = 'The target cloud for the 3-Tier';

          $em->persist($cloud_metainput);
          // END Cloud ProductMetaInput

          // START default security group
          $securityGroup = new SecurityGroup();
          $securityGroup->name = new TextProductMetaInput('base');
          $securityGroup->description = new TextProductMetaInput('Port 22 and 80');
          $securityGroup->cloud_id = $cloud_metainput;

          $idx = 0;

          $securityGroup->rules[$idx] = new SecurityGroupRule();
          $securityGroup->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
          $securityGroup->rules[$idx]->ingress_from_port = new NumberProductMetaInput(22);
          $securityGroup->rules[$idx]->ingress_to_port = new NumberProductMetaInput(22);
          $securityGroup->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

          $idx++;

          $securityGroup->rules[$idx] = new SecurityGroupRule();
          $securityGroup->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
          $securityGroup->rules[$idx]->ingress_from_port = new NumberProductMetaInput(80);
          $securityGroup->rules[$idx]->ingress_to_port = new NumberProductMetaInput(80);
          $securityGroup->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

          $em->persist($securityGroup);
          // START default security group

          $serverTemplate = new ServerTemplate();
          $serverTemplate->version = new NumberProductMetaInput(121);
          $serverTemplate->nickname = new TextProductMetaInput('Base ServerTemplate for Linux (v13.2.1)');
          $serverTemplate->publication_id = new TextProductMetaInput('46542');

          $server = new ProvisionableServer();
          $server->cloud_id = $cloud_metainput;
          $server->count = $count_metainput;
          $server->instance_type = new TextProductMetaInput('m1.small');
          $server->security_groups = array($securityGroup);
          $server->server_template = $serverTemplate;
          $server->nickname = new TextProductMetaInput('Base ST');

          $product->name = "Base";
          $product->icon_filename = "redhat.png";
          $product->security_groups = array($securityGroup);
          $product->servers = array($server);
          $product->meta_inputs = array($cloud_metainput, $count_metainput);
          $product->launch_servers = true;
          break;
        case "php3tier":
          // START Cloud ProductMetaInput
          $cloud_metainput = new CloudProductMetaInput();
          $cloud_metainput->default_value = 1;
          $cloud_metainput->input_name = 'cloud';
          $cloud_metainput->display_name = 'Cloud';
          $cloud_metainput->description = 'The AWS cloud to create the 3-Tier in';

          $em->persist($cloud_metainput);
          // END Cloud ProductMetaInput

          // START Text ProductMetaInput
          $text_metainput = new TextProductMetaInput();
          $text_metainput->default_value = 'phparray';
          $text_metainput->input_name = 'array_tag';
          $text_metainput->display_name = 'Array Vote Tag';
          $text_metainput->description = 'A tag used to identify the autoscaling array';

          $em->persist($text_metainput);
          // END Text ProductMetaInput

          // START Deployment Name MetaInput
          $deployment_name = new TextProductMetaInput();
          $deployment_name->default_value = 'PHP 3-Tier';
          $deployment_name->input_name = 'deployment_name';
          $deployment_name->display_name = 'Deployment Name';
          $deployment_name->description = 'The name of the deployment which will be created in RightScale';

          $em->persist($deployment_name);
          // END Deployment Name MetaInput

          // START php-default Security Group
          $php_default_sg = new SecurityGroup();
          $php_default_sg->name = new TextProductMetaInput('php-default');
          $php_default_sg->description = new TextProductMetaInput('PHP 3-Tier');
          $php_default_sg->cloud_id = $cloud_metainput;

          $em->persist($php_default_sg);
          // END php-default Security Group

            // START php-lb Security Group
          $php_lb_sg = new SecurityGroup();
          $php_lb_sg->name = new TextProductMetaInput("php-lb");
          $php_lb_sg->description = new TextProductMetaInput("PHP 3-Tier");
          $php_lb_sg->cloud_id = $cloud_metainput;

          $em->persist($php_lb_sg);
          // END php-lb Security Group

          // START php-app Security Group
          $php_app_sg = new SecurityGroup();
          $php_app_sg->name = new TextProductMetaInput('php-app');
          $php_app_sg->description = new TextProductMetaInput('PHP 3-Tier');
          $php_app_sg->cloud_id = $cloud_metainput;

          $em->persist($php_app_sg);
          // END php-app Security Group

          // START php-mysql Security Group
          $php_mysql_sg = new SecurityGroup();
          $php_mysql_sg->name = new TextProductMetaInput("php-mysql");
          $php_mysql_sg->description = new TextProductMetaInput("PHP 3-Tier");
          $php_mysql_sg->cloud_id = $cloud_metainput;

          $em->persist($php_mysql_sg);
          // END php-mysql Security Group


          // START Add security group rules
          $idx = 0;

          $php_default_sg->rules[$idx] = new SecurityGroupRule();
          $php_default_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
          $php_default_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(22);
          $php_default_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(22);
          $php_default_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

          $em->persist($php_default_sg);

          $idx = 0;

          $php_lb_sg->rules[$idx] = new SecurityGroupRule();
          $php_lb_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
          $php_lb_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(80);
          $php_lb_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(80);
          $php_lb_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
          $idx++;

          $em->persist($php_lb_sg);

          $idx = 0;

          $php_app_sg->rules[$idx] = new SecurityGroupRule();
          $php_app_sg->rules[$idx]->ingress_group = $php_lb_sg;
          $php_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(8000);
          $php_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(8000);
          $php_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

          $em->persist($php_app_sg);

          $idx = 0;

          $php_mysql_sg->rules[$idx] = new SecurityGroupRule();
          $php_mysql_sg->rules[$idx]->ingress_group = $php_app_sg;
          $php_mysql_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(3306);
          $php_mysql_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(3306);
          $php_mysql_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
          $idx++;

          $php_mysql_sg->rules[$idx] = new SecurityGroupRule();
          $php_mysql_sg->rules[$idx]->ingress_group =  $php_mysql_sg;
          $php_mysql_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(3306);
          $php_mysql_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(3306);
          $php_mysql_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
          $idx++;

          $em->persist($php_mysql_sg);
          // END Add security group rules

          // START app_server 1
          $app_server_st = new ServerTemplate();
          $app_server_st->version = new NumberProductMetaInput(153);
          $app_server_st->nickname = new TextProductMetaInput('PHP App Server (v13.2.1)');
          $app_server_st->publication_id = new TextProductMetaInput('46547');

          $app_server = new ProvisionableServer();
          $app_server->cloud_id = $cloud_metainput;
          $app_server->count = new NumberProductMetaInput(1);
          $app_server->instance_type = new TextProductMetaInput('m1.medium');
          $app_server->security_groups = array($php_default_sg, $php_app_sg);
          $app_server->server_template = $app_server_st;
          $app_server->nickname = new TextProductMetaInput("App1");

          $em->persist($app_server);
          // END app_server 1

          // START php_db server(s)
          $php_db_st = new ServerTemplate();
          $php_db_st->version = new NumberProductMetaInput(102);
          $php_db_st->nickname = new TextProductMetaInput('Database Manager for MySQL 5.5 (v13.2.1)');
          $php_db_st->publication_id = new TextProductMetaInput('46554');

          $php_db = new ProvisionableServer();
          $php_db->cloud_id = $cloud_metainput;
          $php_db->count = new NumberProductMetaInput(2);
          $php_db->instance_type = new TextProductMetaInput("m1.large");
          $php_db->security_groups = array($php_default_sg, $php_mysql_sg);
          $php_db->server_template = $php_db_st;
          // This is really just a prefix, it'll get an index numeral appended to it.
          $php_db->nickname = new TextProductMetaInput("DB");

          $em->persist($php_db);
          // END php_db server(s)

          // START php_lb server(s)
          $php_lb_st = new ServerTemplate();
          $php_lb_st->version = new NumberProductMetaInput(136);
          $php_lb_st->nickname = new TextProductMetaInput('Load Balancer with HAProxy (v13.2.1)');
          $php_lb_st->publication_id = new TextProductMetaInput('46546');

          $php_lb = new ProvisionableServer();
          $php_lb->cloud_id = $cloud_metainput;
          $php_lb->count = new NumberProductMetaInput(2);
          $php_lb->instance_type = new TextProductMetaInput("m1.small");
          $php_lb->security_groups = array($php_default_sg, $php_lb_sg);
          $php_lb->server_template = $php_lb_st;
          // This is really just a prefix, it'll get an index numeral appended to it.
          $php_lb->nickname = new TextProductMetaInput("LB");

          $em->persist($php_lb);
          // END php_lb server(s)

          // START App Server Array
          $php_server_ary = new ProvisionableServerArray();
          $php_server_ary->cloud_id = $cloud_metainput;
          $php_server_ary->min_count = new NumberProductMetaInput(1);
          $php_server_ary->max_count = new NumberProductMetaInput(10);
          $php_server_ary->type = new TextProductMetaInput("alert");
          $php_server_ary->tag = $text_metainput;
          $php_server_ary->instance_type = new TextProductMetaInput('m1.medium');
          $php_server_ary->security_groups = array($php_default_sg, $php_app_sg);
          $php_server_ary->server_template = $app_server_st;
          $php_server_ary->nickname = new TextProductMetaInput("PHPArray");

          $em->persist($php_server_ary);
          // END App Server Array

          // START CPU Scale Up Alert
          $cpu_scale_up_alert = new AlertSpec();
          $cpu_scale_up_alert->name = new TextProductMetaInput('CPU Scale Up');
          $cpu_scale_up_alert->file = new TextProductMetaInput('cpu-0/cpu-idle');
          $cpu_scale_up_alert->variable = new TextProductMetaInput('value');
          $cpu_scale_up_alert->cond = new TextProductMetaInput('<');
          $cpu_scale_up_alert->threshold = new TextProductMetaInput('60');
          $cpu_scale_up_alert->duration = new NumberProductMetaInput(2);
          $cpu_scale_up_alert->subjects = array($app_server);
          $cpu_scale_up_alert->action = new TextProductMetaInput('vote');
          $cpu_scale_up_alert->vote_tag = $text_metainput;
          $cpu_scale_up_alert->vote_type = new TextProductMetaInput('grow');

          $em->persist($cpu_scale_up_alert);
          // END CPU Scale Up Alert

          // START CPU Scale Down Alert
          $cpu_scale_down_alert = new AlertSpec();
          $cpu_scale_down_alert->name = new TextProductMetaInput('CPU Scale Down');
          $cpu_scale_down_alert->file = new TextProductMetaInput('cpu-0/cpu-idle');
          $cpu_scale_down_alert->variable = new TextProductMetaInput('value');
          $cpu_scale_down_alert->cond = new TextProductMetaInput('>');
          $cpu_scale_down_alert->threshold = new TextProductMetaInput('80');
          $cpu_scale_down_alert->duration = new NumberProductMetaInput(2);
          $cpu_scale_down_alert->subjects = array($app_server);
          $cpu_scale_down_alert->action = new TextProductMetaInput('vote');
          $cpu_scale_down_alert->vote_tag = $text_metainput;
          $cpu_scale_down_alert->vote_type = new TextProductMetaInput('shrink');

          $em->persist($cpu_scale_down_alert);
          // END CPU Scale Down Alert

          $product->name = "PHP 3-Tier";
          $product->icon_filename = "php.png";
          $product->security_groups = array($php_app_sg, $php_default_sg, $php_lb_sg, $php_mysql_sg);
          $product->servers = array($app_server, $php_db, $php_lb);
          $product->arrays = array($php_server_ary);
          $product->alerts = array($cpu_scale_up_alert, $cpu_scale_down_alert);
          $product->meta_inputs = array($cloud_metainput, $text_metainput, $deployment_name);
          $product->launch_servers = false;
          break;
      }
    } catch (\Exception $e) {
      $this->getLogger()->err($e->getMessage());
      $this->getLogger()->err($e->getTraceAsString());
    }

    try {
      $em->persist($product);
      $em->flush();
    } catch (\Exception $e) {
      $this->getLogger()->err($e->getMessage());
      $this->getLogger()->err($e->getTraceAsString());
    }
    return array('messages' => $collection_writer->messages);
	}

}

