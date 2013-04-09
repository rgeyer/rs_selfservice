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

use Zend\Http\Request;
use Zend\Http\Response;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Controller\AbstractRestfulController;

use SelfService\Entity\ProvisionedDeployment;

class ProvisionedProductController extends AbstractRestfulController {

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
    $provisionedProductService = $this->getServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
    $provisionedProduct = $provisionedProductService->create($data);
    $response =  $this->getResponse();
    $response->setStatusCode(Response::STATUS_CODE_201);
    $response->getHeaders()->addHeaderLine('Location',
      $this->url()->fromRoute('api-provisionedproduct', array('id' => $provisionedProduct->id)));
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

  public function objectsAction() {
    $retval = array();
    if($this->getRequest()->getMethod() != Request::METHOD_POST) {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_405);
      $this->getResponse()->getHeaders()->addHeaderLine('Allow', array('POST'));
      $retval['message'] = "Only the POST (or create) method is allowed.";
    } else {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_201);
      $required_params = array('href', 'type');
      $post_params = $this->params()->fromPost();
      $missing_required_params = array_diff($required_params, array_keys($post_params));
      if(count($missing_required_params) > 0) {
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
        $retval['message'] = 'Missing required fields: '.join(',', $missing_required_params);
      } else {
        $object = null;
        switch($post_params['type']) {
          case "rs.deployment":
            $object = new ProvisionedDeployment(array('href'=>$post_params['href']));
            break;
        }
        $em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        if($object != null) {
          $em->persist($object);
          $em->flush();
        }
        $retval = array_merge($retval, array('id' => $this->params('id'), 'post' => $this->params()->fromPost()));
      }
    }
    $retval['code'] = $this->getResponse()->getStatusCode();
    return new JsonModel($retval);
  }
}