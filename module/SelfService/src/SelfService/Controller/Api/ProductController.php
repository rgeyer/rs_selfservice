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

class UserController extends AbstractRestfulController {


  /**
   * @return \SelfService\Service\Entity\UserService
   */
  protected function getUserService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\UserService');
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
    $retval = array();
    $response =  $this->getResponse();
    $body = strval($this->getRequest()->getContent());
    $post_params = get_object_vars(json_decode($body));
    if(array_key_exists('email', $post_params)) {
      if(array_key_exists('password', $post_params)) {
        $post_params['password'] = md5($post_params['password']);
      }
      $userservice = $this->getUserService();
      $user = $userservice->create($post_params);
      $response->setStatusCode(Response::STATUS_CODE_201);
      $response->getHeaders()->addHeaderLine('Location',
        $this->url()->fromRoute('api-user', array('id' => $user->id)));
    } else {
      $response->setStatusCode(Response::STATUS_CODE_400);
      $retval['message'] = "An email address is required";
    }
    $retval['code'] = $this->getResponse()->getStatusCode();
    $response->sendHeaders();
    return new JsonModel($retval);
  }

  /**
   * Delete an existing resource
   *
   * @param  mixed $id
   * @return mixed
   */
  public function delete($id)
  {
    $retval = array();
    $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
    try {
      $this->getUserService()->remove($this->params('id'));
    } catch (NotFoundException $e) {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
    }
    $this->getResponse()->sendHeaders();
    return new JsonModel($retval);
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

  public function authorizeAction()
  {
    $retval = array();
    if($this->getRequest()->getMethod() != Request::METHOD_POST) {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_405);
      $this->getResponse()->getHeaders()->addHeaderLine('Allow', array('POST'));
      $retval['message'] = "Only the POST method is allowed.";
    } else {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
      try {
        $this->getUserService()->authorize($this->params('id'));
      } catch (NotFoundException $e) {
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
      }
    }
    $retval['code'] = $this->getResponse()->getStatusCode();
    return new JsonModel($retval);
  }

  public function deauthorizeAction()
  {
    $retval = array();
    if($this->getRequest()->getMethod() != Request::METHOD_POST) {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_405);
      $this->getResponse()->getHeaders()->addHeaderLine('Allow', array('POST'));
      $retval['message'] = "Only the POST method is allowed.";
    } else {
      $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
      try {
        $this->getUserService()->deauthorize($this->params('id'));
      } catch (NotFoundException $e) {
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_404);
      }
    }
    $retval['code'] = $this->getResponse()->getStatusCode();
    return new JsonModel($retval);
  }
}