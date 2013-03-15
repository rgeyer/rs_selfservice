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

class JobberController extends \SelfService\controller\BaseController {
	protected $_session_ns;
	
	private function addJob() {
		if(!$_session_ns->jobs) {
			$_session_ns->jobs = array();
		}
		
		$username = Zend_Auth::getInstance()->getIdentity()->name;
		
		$job = array();
		$job['id'] = sha1(time() . $username);
		$job['status'] = 'started';
		
		$_session_ns->jobs[] = $job;
		return $job;
	}
	
	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		Zend_Session::start();
		$this->_session_ns = new Zend_Session_Namespace('jobber');
	}
	
	public function createAction() {
		$response = array('result' => 'success');
		$response['job'] = $this->addJob();
		$this->_helper->json->sendJson($response);
	}
	
	public function listAction() {
		
	}
	
	public function statusAction() {
		
	}
}