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
use SelfService\Entity\Provisionable\Product;
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
use SelfService\Entity\ProvisionedSshKey;
use SelfService\Entity\ProvisionedServer;
use SelfService\Entity\ProvisionedProduct;
use SelfService\Entity\ProvisionedDeployment;
use Zend\Authentication\AuthenticationService;
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
    $collection_writer = new CollectionWriter();
    $this->getLogger()->addWriter($collection_writer);

    $em = $this->getEntityManager();
    try {
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

      $product = new Product();
      $product->name = "Base";
      $product->icon_filename = "redhat.png";
      $product->security_groups = array($securityGroup);
      $product->servers = array($server);
      $product->meta_inputs = array($cloud_metainput, $count_metainput);
      $product->launch_servers = true;

    } catch (\Exception $e) {
      $this->getLogger()->err($e->getMessage());
      $this->getLogger()->err(print_r($e,true));
    }

    try {
      $em->persist($product);
      $em->flush();
    } catch (\Exception $e) {
      $this->getLogger()->err($e->getMessage());
      $this->getLogger()->err(print_r($e,true));
    }
    return array('messages' => $collection_writer->messages);
	}

}

