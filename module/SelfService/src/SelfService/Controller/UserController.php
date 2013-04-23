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

use Doctrine\ORM\ORMException;
use Zend\View\Model\JsonModel;

/**
 * UserController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class UserController extends BaseController {

  public function unauthorizedAction() {
    return array('use_layout' => true);
  }

  public function authorizeAction() {
    $response = array('result' => 'success');
    $userService = $this->getServiceLocator()->get('SelfService\Service\Entity\UserService');
    try {
      $userService->authorizeByEmail($this->params('email'));
    } catch (ORMException $e) {
      $response['result'] = 'error';
      $response['message'] = $e->getMessage();
    }
    if($this->getRequest() instanceof \Zend\Http\Request) {
      return new JsonModel($response);
    } else {
      return $response;
    }
  }

  public function deauthorizeAction() {
    $response = array('result' => 'success');
    $userService = $this->getServiceLocator()->get('SelfService\Service\Entity\UserService');
    try {
      $userService->deauthorizeByEmail($this->params('email'));
    } catch (ORMException $e) {
      $response['result'] = 'error';
      $response['message'] = $e->getMessage();
    }
    if($this->getRequest() instanceof \Zend\Http\Request) {
      return new JsonModel($response);
    } else {
      return $response;
    }
  }

  public function indexAction() {
    $users = $this->getServiceLocator()->get('SelfService\Service\Entity\UserService')->findAll();

    if($this->getRequest() instanceof \Zend\Http\Request) {
      $actions = array();
      foreach($users as $user) {
        if($user->authorized) {
          $actions[$user->id] = array(
            'deauthorize' => array(
              'uri' => $this->url()->fromRoute('user', array('action' => 'deauthorize', 'email' => urlencode($user->email))),
              'img_path' => 'images/16keyblock.png',
              'is_ajax' => true
            )
          );
        } else {
          $actions[$user->id] = array(
            'authorize' => array(
              'uri' => $this->url()->fromRoute('user', array('action', array('action' => 'authorize', 'email' => urlencode($user->email)))),
              'img_path' => 'images/16key.png',
              'is_ajax' => true
            )
          );
        }
      }
      return array('users' => $users, 'actions' => $actions);
    } else {
      $console = $this->getServiceLocator()->get('console');
      foreach($users as $user) {
        if($user->authorized) {
          $console->writeLine("authorized   -- ".$user->email, \Zend\Console\ColorInterface::GREEN);
        } else {
          $console->writeLine("unauthorized -- ".$user->email, \Zend\Console\ColorInterface::RED);
        }
      }
      return array();
    }
  }

}

