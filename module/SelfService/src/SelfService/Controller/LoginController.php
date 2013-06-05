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

use Zend\Session\Container;
use Zend\Authentication\Result;
use SelfService\Zend\Authentication\Adapter\GoogleAuthAdapter;

/**
 * LoginController
 *
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class LoginController extends BaseController {

  /**
   * @return \SelfService\Zend\Authentication\Adapter\GoogleAuthAdapter
   */
  protected function getAuthenticationAdapter() {
    return $this->getServiceLocator()->get('AuthenticationAdapter');
  }

  /**
   * @return \Zend\Authentication\AuthenticationService
   */
  protected function getAuthenticationService() {
    return $this->getServiceLocator()->get('AuthenticationService');
  }

  public function indexAction() {
    return array('form_action' => $this->url()->fromRoute('login', array('action' => 'process')));
  }

  public function processAction() {
    $response = array();
    $authAdapter = $this->getAuthenticationAdapter();
    if (!$authAdapter->getOidMode()) {
      $this->redirect()->toUrl($authAdapter->getOpenIdUrl());
    } elseif ($authAdapter->getOidMode() == 'cancel') {
      $response['message'] = 'You cancelled authenticating with Google, refresh if you\'d like to try again';
    } else {
      $authSvc = $this->getAuthenticationService();
      $result = $authSvc->authenticate($authAdapter);
      if ($result->getCode() == Result::SUCCESS) {
        #$sess = new Container('auth');
        #$routematch = $sess->pre_login_route;
        #$this->redirect()->toRoute($routematch->getMatchedRouteName(), $routematch->getParams());
        $this->redirect()->toRoute('home');
      }
    }

    return $response;
  }

  public function logoutAction() {
    $authSvc = $this->getAuthenticationService();
    $authSvc->clearIdentity();
    $this->redirect()->toRoute('login');
  }

}