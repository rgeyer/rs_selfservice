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

class Application_Plugin_WebSessionLogger extends Zend_Controller_Plugin_Abstract {
	
	/**
	 * 
	 * @var Zend_Log
	 */
	protected $log;
	
	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		
		if ($bootstrap->hasResource('Log')) {
	    $this->log = $bootstrap->getResource('Log');
    }

    if($this->log) {
    	$auth = Zend_Auth::getInstance();
    	$principal_oid = 'anonymous';
    	$principal_name = 'anonymous';
    	if($auth->hasIdentity()) {
    		$principal_oid = $auth->getIdentity()->oid_url;
    		$principal_name = $auth->getIdentity()->name;
    		$this->log->setEventItem('request_id', sha1(time() . $principal_oid));
    	}
    	
    	$this->log->setEventItem('info', '');
    	$this->log->info("Request started for " . $principal_name);
    }
	}
	
}
