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

namespace SelfService;

class DoctrineAuthAdapter implements \Zend_Auth_Adapter_Interface {
	
	protected $_em;
	
	protected $_email;
	
	protected $_password;
	
	public function __construct($email, $password) {
		$this->_email = $email;
		$this->_password = $password;
		$this->_em = \Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('doctrineEntityManager');
	}
	
	public function authenticate() {		
		$dql = "SELECT u FROM User u WHERE u.email = '$this->_email'";

		$query = $this->_em->createQuery($dql);
		$results = $query->getResult();		
		if(count($results) == 0) {
			return new \Zend_Auth_Result(\Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, null);
		}

		// Skipping a check for Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS because we're using emails
		// which should be globally unique
		
		// TODO: Should probably determine a salting mechanism.
		if(!$results[0]->authenticatePassword($this->_password)) {
			return new \Zend_Auth_Result(\Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, null);
		} else {
			return new \Zend_Auth_Result(\Zend_Auth_Result::SUCCESS, $results[0]);
		}
	}
}