<?php
/*
Copyright (c) 2011 Ryan J. Geyer <me@ryangeyer.com>

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

/**
 * LoginController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class LoginController extends \SelfService\controller\BaseController {
	
	/**
	 * 
	 */
	public function indexAction() {		
		$this->view->assign('form_action', $this->_helper->url('login', 'login', 'default'));		
	}
	
	public function loginAction() {
		$authAdapter = new SelfService\GoogleAuthAdapter($this->em);
		if(!$authAdapter->getOidMode()) {
			$this->view->assign('url', $authAdapter->redirectUrlForGoogleAuth());
			$this->_redirect($authAdapter->redirectUrlForGoogleAuth());
		} elseif ($authAdapter->getOidMode() == 'cancel') {
			$this->view->assign('message', 'You cancelled authenticating with Google, refresh if you\'d like to try again');
		} else {
			$result = Zend_Auth::getInstance()->authenticate(new SelfService\GoogleAuthAdapter($this->em));
			if($result->getCode() == \Zend_Auth_Result::SUCCESS) {
				if(!$result->getIdentity()->name || !$result->getIdentity()->email) {
					$this->_helper->redirector('newprofile', 'user', 'default');
				} else {
					Zend_Session::start();
					$session = new Zend_Session_Namespace('auth');
					$this->_helper->redirector($session->return_action, $session->return_controller, $session->return_module);
					# TODO: Redirect to some logical page, perhaps where they came from?			
					$this->view->assign('message', nl2br(htmlentities(print_r($db_result, true))));
				}
			} else {
				
			}
		}
	}
	
	public function logoutAction() {
		Zend_Auth::getInstance()->clearIdentity();
		$this->_helper->redirector('index', 'index', 'default');
	}

}
