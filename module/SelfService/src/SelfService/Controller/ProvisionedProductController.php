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

use DateTime;
use Zend\View\Model\JsonModel;
use RGeyer\Guzzle\Rs\Model\Mc\SshKey;
use RGeyer\Guzzle\Rs\Model\Mc\Server;
use SelfService\Entity\ProvisionedSshKey;
use SelfService\Entity\ProvisionedServer;
use SelfService\Entity\ProvisionedProduct;
use SelfService\Entity\ProvisionedDeployment;
use SelfService\Entity\ProvisionedSecurityGroup;

/**
 * ProvisionedProductController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com> 
 */
class ProvisionedProductController extends BaseController {
	
	public function indexAction() {
    $products = $this->getEntityManager()->getRepository('SelfService\Entity\ProvisionedProduct')->findAll();

    $actions = array(
      'del' => array(
        'uri_prefix' => $this->url()->fromRoute('provisionedproducts', array('action' => 'cleanup')),
        'img_path' => '/images/delete.png',
        'is_ajax' => true
      ),
      'show' => array(
        'uri_prefix' => $this->url()->fromRoute('provisionedproducts', array('action' => 'show')),
        'img_path' => '/images/info.png'
      )
    );

    return array('provisioned_products' => $products, 'actions' => $actions, 'use_layout' => true);
	}
	
	public function cleanupAction() {
    $response = array('result' => 'success');
    $product_id = $this->params('id');
    if(isset($product_id)) {
      $em = $this->getEntityManager();
      $cleanup_helper = $this->getServiceLocator()->get('rs_cleanup_helper');
      $prov_product = $em->getRepository('SelfService\Entity\ProvisionedProduct')->find($product_id);
      if(count($prov_product) == 1) {
        $keep_going = false;
        do {
          $prov_arrays = array();
          $prov_severs = array();
          $prov_depl = null;
          $prov_sshkeys = array();
          $prov_secgrps = array();
          foreach($prov_product->provisioned_objects as $provisioned_obj) {
            if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedDeployment')) {
              $prov_depl = $provisioned_obj;
            }
            if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedServer')) {
              $prov_servers[] = $provisioned_obj;
            }
            if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedArray')) {
              $prov_arrays[] = $provisioned_obj;
            }
            if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedSshKey')) {
              $prov_sshkeys[] = $provisioned_obj;
            }
            if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedSecurityGroup')) {
              $prov_secgrps[] = $provisioned_obj;
            }
          }

          # Stop and destroy arrays
					if(count($prov_arrays) > 0) {
						foreach($prov_arrays as $prov_array) {
              if($cleanup_helper->cleanupServerArray($prov_array)) {
                $prov_product->provisioned_objects->removeElement($prov_array);
                $em->remove($prov_array);
                $em->flush();
              } else {
                $response['wait_for_decom']['arrays'][] = $prov_array->href;
              }
						}
					}

					# Stop and destroy the servers
					if(count($prov_servers) > 0) {
						foreach($prov_servers as $prov_server) {
              if($cleanup_helper->cleanupServer($prov_server)) {
								$prov_product->provisioned_objects->removeElement($prov_server);
								$em->remove($prov_server);
								$em->flush();
              } else {
                $response['wait_for_decom']['servers'][] = $prov_server->href;
              }
						}
					}

					# Wait up if we're waiting on servers or array instances
					if(	array_key_exists('wait_for_decom', $response) && count($response['wait_for_decom']) > 0) {
            $response['messages'][] = sprintf(
              "There were %d servers still running and %d arrays with running instances.  A terminate request has been sent.  When the servers have been terminated, you can try to delete the product again",
              count($response['wait_for_decom']['servers']),
              count($response['wait_for_decom']['arrays'])
            );
            $keep_going = true;
						break;
					}

					# Destroy the deployment
					if($prov_depl) {
            $cleanup_helper->cleanupDeployment($prov_depl);
						$prov_product->provisioned_objects->removeElement($prov_depl);
						$em->remove($prov_depl);
						$em->flush();
					}

					# Destroy SSH key
					if(count($prov_sshkeys) > 0) {
						foreach($prov_sshkeys as $prov_sshkey) {
              $cleanup_helper->cleanupSshKey($prov_sshkey);
							$prov_product->provisioned_objects->removeElement($prov_sshkey);
							$em->remove($prov_sshkey);
							$em->flush();
						}
					}

					# Destroy SecurityGroups
					if(count($prov_secgrps) > 0) {
            foreach($prov_secgrps as $prov_secgrp) {
              $cleanup_helper->cleanupSecurityGroupRules($prov_secgrp);
            }

            foreach($prov_secgrps as $prov_secgrp) {
              $cleanup_helper->cleanupSecurityGroup($prov_secgrp);
							$prov_product->provisioned_objects->removeElement($prov_secgrp);
							$em->remove($prov_secgrp);
							$em->flush();
            }
					}
        } while ($keep_going);
        if(!$keep_going) {
          $em->remove($prov_product);
          $em->flush();
        }
      }
    }

    return new JsonModel($response);
	}

  public function showAction() {
    $product_id = $this->params('id');
    $servers = array();
    if(isset($product_id)) {
      $client = $this->getServiceLocator()->get('RightScaleAPIClient');
      $em = $this->getEntityManager();
      $prov_product = $em->getRepository('SelfService\Entity\ProvisionedProduct')->find($product_id);
      if(count($prov_product) == 1) {
        $prov_servers = array();
        $prov_depl = null;
        foreach($prov_product->provisioned_objects as $provisioned_obj) {
          if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedDeployment')) {
            $prov_depl = $provisioned_obj;
          }
          if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedServer')) {
            $prov_servers[] = $provisioned_obj;
          }
        }

        $mc_srv_model = $client->newModel('Server');
        foreach($mc_srv_model->index($prov_depl->href) as $server) {
          $stdServer = new \stdClass();
          $stdServer->name = $server->name;
          $stdServer->state = $server->state;
          $stdServer->created_at = $server->created_at;
          $stdServer->href = $server->href;
          foreach($prov_servers as $prov_server) {
            if($server->href == $prov_server->href) {
              $stdServer->prov_server = $prov_server;
            }
          }

          if(!in_array($server->state, array('inactive', 'stopped'))){
            $api_server_model = $client->newModel('Server');
            $api_server_model->find_by_href($stdServer->href);

            $stdServer->ip = $api_server_model->current_instance()->public_ip_addresses[0];
          }

          $servers[] = $stdServer;
        }

        foreach($servers as $idx => $server) {
          if(!$server->actions) {
            $server->actions = array();
          }
          if(in_array($server->state, array('inactive', 'stopped'))) {
            $server->actions['start'] = array(
              'uri_prefix' => $this->url()->fromRoute('provisionedproducts', array('action' => 'serverstart')),
              'img_path' => '/images/plus.png',
              'is_ajax' => true
            );
          }
          if($server->state == 'operational') {
            $server->actions['stop'] = array(
              'uri_prefix' => $this->url()->fromRoute('provisionedproducts', array('action' => 'serverstop')),
              'img_path' => '/images/delete.png',
              'is_ajax' => true
            );
          }
        }
      }
    }

    return array('servers' => $servers);
  }

  public function serverstartAction() {
    $response = array('result' => 'success');
    $server_id = $this->params('id');
    if(isset($server_id)) {
      $client = $this->getServiceLocator()->get('RightScaleAPIClient');
      $em = $this->getEntityManager();
      $prov_server = $em->getRepository('SelfService\Entity\ProvisionedServer')->find($server_id);
      if(count($prov_server) == 1) {
        $server = $client->newModel('Server');
        $server->find_by_href($prov_server->href);
        $server->launch();
      }
    }

    return new JsonModel($response);
  }

  public function serverstopAction() {
    $response = array('result' => 'success');
    $server_id = $this->params('id');
    if(isset($server_id)) {
      $client = $this->getServiceLocator()->get('RightScaleAPIClient');
      $em = $this->getEntityManager();
      $prov_server = $em->getRepository('SelfService\Entity\ProvisionedServer')->find($server_id);
      if(count($prov_server) == 1) {
        $server = $client->newModel('Server');
        $server->find_by_href($prov_server->href);
        $server->terminate();
      }
    }

    return new JsonModel($response);
  }
}
