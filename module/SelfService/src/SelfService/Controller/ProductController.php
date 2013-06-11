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
use SelfService\Zend\Log\Writer\Collection as CollectionWriter;


use DateTime;
use Zend\View\Model\JsonModel;
use RGeyer\Guzzle\Rs\Model\Mc\SshKey;
use RGeyer\Guzzle\Rs\Model\Mc\Server;
use RGeyer\Guzzle\Rs\Model\Mc\ServerArray as ApiServerArray;
use SelfService\Document\ProvisionedProduct;
use SelfService\Document\ProvisionedObject;
use SelfService\Document\Exception\NotFoundException;

/**
 * ProductController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class ProductController extends BaseController {

  /**
   * @return \SelfService\Service\Entity\ProductService
   */
  protected function getProductEntityService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }

  /**
   * @return \SelfService\Service\Entity\ProvisionedProductService
   */
  protected function getProvisionedProductEntityService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
  }

  /**
   * @return \SelfService\Service\Entity\UserService
   */
  protected function getUserEntityService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\UserService');
  }

  public function indexAction() {
    $products = $this->getProductEntityService()->findAll();
    $actions = array();
    foreach($products as $product) {
      $actions[$product->id] = array(
        'delete' => array(
          'uri' => $this->url()->fromRoute('product', array('action' => 'delete', 'id' => $product->id)),
          'img_path' => 'images/delete.png',
          'is_ajax' => true
        ),
        'edit' => array(
          'uri' => $this->url()->fromRoute('product', array('action' => 'edit', 'id' => $product->id)),
          'img_path' => 'images/pencil.png',
          'is_ajax' => false
        )
      );
    }
    return array('actions' => $actions, 'products' => $products);
  }

  public function rendermetaformAction() {
    $client = $this->getServiceLocator()->get('RightScaleAPICache');
    $id = $this->params('id');
    $product = $this->getProductEntityService()->find($id);
    $clouds = array();

    foreach($client->getClouds() as $cloud) {
      $clouds[$cloud->name] = $cloud->id;
    }

    $meta_inputs = array();
    foreach($product->resources as $resource) {
      if(is_a($resource, '\SelfService\Document\AbstractProductInput')) {
        $meta_inputs[] = $resource;
      }
    }

    return array('clouds' => $clouds,'meta_inputs' => $meta_inputs,
                 'id' => $id, 'use_layout' => false);
  }

  public function provisionAction() {
    $response = array('result' => 'success', 'messages' => array());
    $now = time();
    $product_id = $this->params('id');
    if(isset($product_id)) {
      $provisioning_adapter = $this->getServiceLocator()->get('Provisioner');
      try {
        $output_json = $this->getProductEntityService()->toOutputJson($product_id, $this->params()->fromPost());

        $response['messages'][] = sprintf(
          "View your provisioned product in the admin panel <a href='%s'>here</a>.",
          $this->url()->fromRoute('provisionedproducts', array('action' => 'show', 'id' => "foo"))
        );
        $provisioning_adapter->provision($output_json);
      } catch (NotFoundException $e) {
        $response['result'] = 'error';
        $response['error'] = 'A product with id '.$product_id.' was not found.';
        $this->getLogger()->err($response['error']);
      }
    } else {
      $response['result'] = 'error';
      $response['error'] = 'A product id was not supplied';
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

    $relative_path = '';
    if($params['path']) {
      $relative_path = $params['name'];
    } else {
      $relative_path = __DIR__.'/../../../../../products/'.$params['name'].'.manifest.json';
    }
    $manifest_path = realpath($relative_path);

    if(!$manifest_path) {
      $this->getLogger()->err("No file existed at ".$relative_path.". If you supplied the full or relative path to a manifest file make sure you use the --path flag and that the file exists.  Otherwise, make sure that a manifest with the product name exists in the ./products directory.");
    } else {
      $this->getLogger()->info("Loading manifest from ". $manifest_path);
      # TODO: This try/catch is vestigial from when actual methods were being
      # called to make ORM requests to populate the DB. Probably not necessary
      # anymore, but needs more thought.
      try {
        $manifest = json_decode(file_get_contents($manifest_path));
        if(!property_exists($manifest, 'product_json')) {
          $this->getLogger()->err("No 'product_json' was specified, nothing to import.");
        } else {
          # Assume product_json is an absolute path to a file
          $product_path = realpath($manifest->product_json);
          if(!$product_path) {
            # Perhaps product_json is a path relative to the manifest
            $parent_dir = dirname($manifest_path);
            $next_try_path = $parent_dir.'/'.$manifest->product_json;
            $this->getLogger()->warn(sprintf("Assuming 'product_json' in the manifest file was an absolute path but couldn't find anything at (%s).  Trying (%s) as though 'product_json' were relative to the manifest file", $manifest->product_json, $next_try_path));
            $product_path = realpath($next_try_path);
          }
          if(!$product_path) {
            $this->getLogger()->err("The file referenced by product_json does not exist.");
          } else {
            $this->getProductEntityService()->createFromJson(file_get_contents($product_path));
          }
        }
      } catch (\Exception $e) {
        $this->getLogger()->err($e->getMessage());
        $this->getLogger()->err($e->getTraceAsString());
      }
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

  public function deleteAction() {
    $response = array('result' => 'success');
    $productService = $this->serviceLocator->get('SelfService\Service\Entity\ProductService');
    $productService->remove($this->params('id'));
    return new JsonModel($response);
  }

  public function editAction() {
    $icons_dir = __DIR__.'/../../../../../public/images/icons';
    $icons = glob($icons_dir.'/*.png');
    foreach($icons as $idx => $icon) {
      $icons[$idx] = basename($icon);
    }
    $productService = $this->serviceLocator->get('SelfService\Service\Entity\ProductService');
    return array('product' => $productService->find($this->params('id')), 'icons' => $icons);
  }

  public function updateAction() {
    $postParams = $this->params()->fromPost();
    if($this->params()->fromPost('launch_servers', 'off') != 'unset') {
      $postParams['launch_servers'] = strtolower($postParams['launch_servers']) == 'on';
    } else {
      $postParams['launch_servers'] = false;
    }
    $response = array('result' => 'success');
    $productService = $this->serviceLocator->get('SelfService\Service\Entity\ProductService');
    $productService->update($this->params('id'), $postParams);
    return new JsonModel($response);
  }

}

