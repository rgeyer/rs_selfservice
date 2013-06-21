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
use SelfService\Document\Exception\NotFoundException;

/**
 * ProvisionedProductController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com> 
 */
class ProvisionedProductController extends BaseController {

  /**
   * @return \SelfService\Service\Entity\ProvisionedProductService
   */
  protected function getProvisionedProductEntityService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
  }
	
	public function indexAction() {
    $products = $this->getProvisionedProductEntityService()->findAll();

    $actions = array(
      'del' => array(
        'url_parts' => array('controller' => 'provisionedproducts', 'action' => 'cleanup'),
        'img_path' => 'images/delete.png',
        'is_ajax' => true
      ),
      'show' => array(
        'url_parts' => array('controller' => 'provisionedproducts', 'action' => 'show'),
        'img_path' => 'images/info.png'
      )
    );

    return array('provisioned_products' => $products, 'actions' => $actions, 'use_layout' => true);
	}
	
	public function cleanupAction() {
    $response = array('result' => 'success', 'messages' => array());
    $product_id = $this->params('id');
    if(isset($product_id)) {
      $provisioning_adapter = $this->getServiceLocator()->get('Provisioner');
      try {
        $provisioned_product = $this->getProvisionedProductEntityService()->find($product_id);
        $provisioned_products = array();
        foreach($provisioned_product->provisioned_objects as $object) {
          $provisioned_products[] = $object;
        }
        $provisioning_adapter->cleanup($product_id, json_encode($provisioned_products));
        $response['messages'] = array_merge($response['messages'], $provisioning_adapter->getMessages(true));
      } catch (NotFoundException $e) {
        $response['result'] = 'error';
        $response['error'] = $e->getMessage();
        $this->getLogger()->err($response['error']);
      }
    } else {
      $response['result'] = 'error';
      $response['error'] = 'A provisioned product id was not supplied';
      $this->getLogger()->err($response['error']);
    }

    return new JsonModel($response);
	}

  public function showAction() {
    $product_id = $this->params('id');
    $servers = array();
    if(isset($product_id)) {
      $client = $this->getServiceLocator()->get('RightScaleAPIClient');
      $service = $this->getProvisionedProductEntityService();
      $prov_product = $service->find($product_id);
      if(count($prov_product) == 1) {
        $prov_servers = array();
        $prov_depl = null;
        foreach($prov_product->provisioned_objects as $provisioned_obj) {
          if($provisioned_obj->type == "deployment") {
            $prov_depl = $provisioned_obj;
          }
          if($provisioned_obj->type == "server") {
            $prov_servers[] = $provisioned_obj;
          }
        }

        $mc_srv_model = $client->newModel('Server');
        foreach($mc_srv_model->index($prov_depl->href) as $server) {
          $stdServer = new \stdClass();
          $stdServer->provisioned_product_id = $product_id;
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
              'url_parts' => array('controller' => 'provisionedproducts', 'action' => 'serverstart'),
              'img_path' => 'images/plus.png',
              'is_ajax' => true
            );
          }
          if($server->state == 'operational') {
            $server->actions['stop'] = array(
              'url_parts' => array('controller' => 'provisionedproducts', 'action' => 'serverstop'),
              'img_path' => 'images/delete.png',
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
      $service = $this->getProvisionedProductEntityService();
      $prov_prod = $service->find($server_id);
      $prov_prod_object_id = $this->params('provisioned_object_id');
      foreach($prov_prod->provisioned_objects as $object) {
        if($object->id == $prov_prod_object_id) {
          $server = $client->newModel('Server');
          $server->find_by_href($object->href);
          $server->launch();
        }
      }
    }

    return new JsonModel($response);
  }

  public function serverstopAction() {
    $response = array('result' => 'success');
    $server_id = $this->params('id');
    if(isset($server_id)) {
      $client = $this->getServiceLocator()->get('RightScaleAPIClient');
      $service = $this->getProvisionedProductEntityService();
      $prov_prod = $service->find($server_id);
      $prov_prod_object_id = $this->params('provisioned_object_id');
      foreach($prov_prod->provisioned_objects as $object) {
        if($object->id == $prov_prod_object_id) {
          $server = $client->newModel('Server');
          $server->find_by_href($object->href);
          $server->terminate();
        }
      }
    }

    return new JsonModel($response);
  }
}
