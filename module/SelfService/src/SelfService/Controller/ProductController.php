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
use SelfService\Entity\Provisionable\MetaInputs\InstanceTypeProductMetaInput;


use DateTime;
use Zend\View\Model\JsonModel;
use RGeyer\Guzzle\Rs\Model\Mc\SshKey;
use RGeyer\Guzzle\Rs\Model\Mc\Server;
use RGeyer\Guzzle\Rs\Model\Mc\ServerArray as ApiServerArray;
use SelfService\Entity\ProvisionedSshKey;
use SelfService\Entity\ProvisionedServer;
use SelfService\Entity\ProvisionedProduct;
use SelfService\Entity\ProvisionedDeployment;
use SelfService\Entity\ProvisionedArray;
use SelfService\Entity\ProvisionedSecurityGroup;

/**
 * ProductController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class ProductController extends BaseController {

  public function rendermetaformAction() {
    $client = $this->getServiceLocator()->get('RightScaleAPICache');
    $id = $this->params('id');
    $product = $this->getEntityManager()->getRepository('SelfService\Entity\Provisionable\Product')->find($id);
    $clouds = array();

    foreach($client->getClouds() as $cloud) {
      $clouds[$cloud->name] = $cloud->id;
    }

    return array('clouds' => $clouds, 'meta_inputs' => $product->meta_inputs, 'id' => $id, 'use_layout' => false);
  }

  public function provisionAction() {
    $response = array('result' => 'success', 'messages' => array());
    $now = time();
    $product_id = $this->params('id');
    if(isset($product_id)) {
      $em = $this->getEntityManager();
      $prov_helper = $this->getServiceLocator()->get('rs_provisioning_helper');
      $product = $em->getRepository('SelfService\Entity\Provisionable\Product')->find($product_id);
      if(count($product) == 1) {
        $authSvc = $this->getServiceLocator()->get('AuthenticationService');

        $product->mergeMetaInputs($this->params()->fromPost());
        $prov_prod = new ProvisionedProduct();
        $prov_prod->createdate = new DateTime();
        $prov_prod->owner = $em->getRepository('SelfService\Entity\User')->find($authSvc->getIdentity()->id);
        $prov_prod->product = $product;
        # TODO: Potentially failing calls without a try/catch!
        $em->persist($prov_prod);
        $em->flush();

        $response['messages'][] = sprintf(
          "View your provisioned product in the admin panel <a href='%s'>here</a>.",
          $this->url()->fromRoute('admin/provisionedproducts').'/provisionedproducts/show/'.$prov_prod->id
        );

        # TODO: Add a link to the RightScale deployment based on a configuration flag and/or user role
        $prov_helper->setTags(array('rsss:provisioned_product_id='.$prov_prod->id));
        try {
          # Provision and record deployment
          $deplname = sprintf("rsss-%s-%s", $product->name, $now);
          $deplname = $this->params()->fromPost('deployment_name', $deplname);
          $depldesc = sprintf("Created by rs_selfservice for the '%s' product", $product->name);
          $depl = $prov_helper->provisionDeployment($deplname, $depldesc, $product->parameters);
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

          # Provision and record alert specs
          $this->getLogger()->info(sprintf("About to provision %d alert specs", count($product->alerts)));
          foreach($product->alerts as $alert) {
            $prov_helper->provisionAlertSpec($alert);
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
    try {
      call_user_func("\\SelfService\\Product\\".$params['name']."::add",$em);
    } catch (\Exception $e) {
      $this->getLogger()->err($e->getMessage());
      $this->getLogger()->err($e->getTraceAsString());
    }

    return join("\n",$collection_writer->messages)."\n";
	}

  public function rideimportAction() {
    switch ($this->getRequest()->getMethod()) {
      case "POST":
        $response = array('result' => 'success');
        $productService = $this->serviceLocator->get('SelfService\Service\Entity\ProductService');
        $productService->createFromRideJson($this->params()->fromPost('dep'));
        return new JsonModel($response);
        break;
      case "GET":
        return array();
        break;
      default:
        break;
    }
    return array();
  }

}

