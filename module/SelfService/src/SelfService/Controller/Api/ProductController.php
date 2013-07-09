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

namespace SelfService\Controller\Api;

use SelfService\Document\Exception\NotFoundException;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Controller\AbstractRestfulController;

class ProductController extends AbstractRestfulController {


  /**
   * @return \SelfService\Service\Entity\ProductService
   */
  protected function getProductService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }

  /**
   * @return \SelfService\Service\Entity\ProvisionedProductService
   */
  protected function getProvisionedProductService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
  }

  /**
   * @return \SelfService\Provisioner\AbstractProvisioner
   */
  protected function getProvisioningAdapter() {
    return $this->getServiceLocator()->get('Provisioner');
  }

  /**
   * @return \Zend\Log\Logger
   */
  protected function getLogger() {
    return $this->getServiceLocator()->get('logger');
  }

  /**
   * Get response object
   *
   * @return \Zend\Http\Response
   */
  public function getResponse()
  {
    return parent::getResponse();
  }

  /**
   * Create a new resource
   *
   * @param  mixed $data
   * @return mixed
   */
  public function create($data)
  {
    $this->getResponse()->setStatusCode(Response::STATUS_CODE_501);
    $this->getResponse()->sendHeaders();
    return new JsonModel();
  }

  /**
   * Delete an existing resource
   *
   * @param  mixed $id
   * @return mixed
   */
  public function delete($id)
  {
    $this->getResponse()->setStatusCode(Response::STATUS_CODE_501);
    $this->getResponse()->sendHeaders();
    return new JsonModel();
  }

  /**
   * Return single resource
   *
   * @param  mixed $id
   * @return mixed
   */
  public function get($id)
  {
    $this->getResponse()->setStatusCode(Response::STATUS_CODE_501);
    $this->getResponse()->sendHeaders();
    return new JsonModel();
  }

  /**
   * Return list of resources
   *
   * @return mixed
   */
  public function getList()
  {
    $this->getResponse()->setStatusCode(Response::STATUS_CODE_501);
    $this->getResponse()->sendHeaders();
    return new JsonModel();
  }

  /**
   * Update an existing resource
   *
   * @param  mixed $id
   * @param  mixed $data
   * @return mixed
   */
  public function update($id, $data)
  {
    $this->getResponse()->setStatusCode(Response::STATUS_CODE_501);
    $this->getResponse()->sendHeaders();
    return new JsonModel();
  }

  /**
   * Note: When parameters are provided for instance_type or datacenter inputs the default_value
   * is overwritten with the selection which was passed in.
   * @return JsonModel
   */
  public function inputsAction() {
    $retval = array();
    if($this->getRequest()->getMethod() != Request::METHOD_POST) {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_405);
      $this->getResponse()->getHeaders()->addHeaderLine('Allow', array('POST'));
      $retval['messages'][] = "Only the POST method is allowed.";
    } else {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
      try {
        $inputs = $this->getProductService()->inputs($this->params('id'), $this->params()->fromPost());
        foreach($inputs as $input) {
          $stdClass = $this->getProductService()->odmToStdClass($input);
          $stdClass->values = $input->values;
          $stdClass->cloud_href = $input->cloud_href;
          $retval[] = $stdClass;
        }
      } catch (NotFoundException $e) {
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
        $retval['messages'] = array($e->getMessage());
      }
    }
    return new JsonModel($retval);
  }

  public function provisionAction() {
    $retval = array();
    if($this->getRequest()->getMethod() != Request::METHOD_POST) {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_405);
      $this->getResponse()->getHeaders()->addHeaderLine('Allow', array('POST'));
      $retval['messages'][] = "Only the POST method is allowed.";
    } else {
      $product_id = $this->params('id');
      try {
        $provisioning_adapter = $this->getProvisioningAdapter();
        $prov_prod = $this->getProvisionedProductService()->create(array());

        $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);

        $this->getLogger()->debug("Calling toOutputJson with the following params ".print_r($this->params()->fromPost(), true));
        $output_json = $this->getProductService()->toOutputJson($product_id, $this->params()->fromPost());

        $retval['messages'][] = sprintf(
          "View your provisioned product in the admin panel <a href='%s'>here</a>.",
          $this->url()->fromRoute('provisionedproducts', array('action' => 'show', 'id' => $prov_prod->id))
        );

        $provisioning_adapter->provision($prov_prod->id, $output_json);
        $retval['messages'] = array_merge($retval['messages'], $provisioning_adapter->getMessages(true));

        $this->getResponse()->getHeaders()->addHeaderLine('Location',
          $this->url()->fromRoute('api-provisionedproduct', array('id' => $prov_prod->id)));
      } catch (NotFoundException $e) {
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
        $retval['messages'] = array($e->getMessage());
      } catch (\Exception $e) {
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_500);
        $retval['messages'] = array($e->getMessage());
        $this->getLogger()->err("An error occurred attempting to provision ".$product_id." Error: ".$e->getMessage()." Trace: ".$e->getTraceAsString());
      }
    }
    return new JsonModel($retval);
  }
}