<?php
/*
Copyright (c) 2012 Ryan J. Geyer <me@ryangeyer.com>

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

class Application_Plugin_GoogleAuth extends Zend_Controller_Plugin_Abstract {
	
	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$controller = $this->getRequest()->getControllerName();
		$action = $this->getRequest()->getActionName();
		if ($controller == 'login') {
			return;
		}
		# TODO: Look up the user in the DB. If they're not there, still force a login.
		$redirect_to_auth = false;
		$auth = Zend_Auth::getInstance();
		if(!$auth->hasIdentity()) {			
			$redirect_to_auth = true;
		} else {
			$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
			$em = $bootstrap->getResource('doctrineEntityManager');
			
			$user = $em->getRepository('User')->findOneBy(array('oid_url' => $auth->getIdentity()->oid_url));
			$redirect_to_auth = ($user == null);
		}
		
		if($redirect_to_auth) {
			Zend_Session::start();
			$session = new Zend_Session_Namespace('auth');
			$session->return_controller = $this->getRequest()->getControllerName();
			$session->return_module = $this->getRequest()->getModuleName();
			$session->return_action = $this->getRequest()->getActionName();
			$this->getRequest()->setModuleName('default')->setControllerName('login')->setActionName('index');
		}
	}
}