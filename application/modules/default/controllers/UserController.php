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

/**
 * UserController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class UserController extends \SelfService\controller\BaseController {
	public function newprofileAction() {
		$this->view->assign('form_action', $this->_helper->url('update', 'user', 'default'));
	}
	
	public function updateAction() {
		$user = $this->em->getRepository('User')->find(Zend_Auth::getInstance()->getIdentity()->id);
		$user->email = $this->getRequest()->getParam('email');
		$user->name = $this->getRequest()->getParam('name');
		$this->em->persist($user);
		$this->em->flush();
		Zend_Auth::getInstance()->getIdentity()->email = $user->email;
		Zend_Auth::getInstance()->getIdentity()->name = $user->name;
		Zend_Session::start();
		$session = new Zend_Session_Namespace('auth');
		$this->_helper->redirector($session->return_action, $session->return_controller, $session->return_module);
	}
}
