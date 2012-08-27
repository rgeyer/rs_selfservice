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

namespace SelfService;

use Doctrine\Tests\Common\Annotations\False;

class GoogleAuthAdapter implements \Zend_Auth_Adapter_Interface {	

	/**
	 * @var \LightOpenID
	 */
	protected $_oid;
	
	protected $em;
	
	public function __construct($entityManager) {
		# TODO: Put in the discovered hostname/domain here?
		$this->_oid = new \LightOpenID('local.rsss.com');
		$this->em = $entityManager;
	}
	
	/**
	 * @return The string value of LightOpenID->mode
	 */
	public function getOidMode() {
		return $this->_oid->mode;
	}
	
	public function redirectUrlForGoogleAuth() {
		$this->_oid->identity = 'https://www.google.com/accounts/o8/id';
		return $this->_oid->authUrl();
	}
	
	public function authenticate() {
		if($this->_oid->mode == 'cancel') {
			return new \Zend_Auth_Result(\Zend_Auth_Result::FAILURE, $this->_oid);
		} elseif ($this->_oid->validate()) {
			$user = $this->em->getRepository('User')->findOneBy(array('oid_url' => $this->_oid->identity));
			if(!$user) {
				$user = new \User();
				$user->oid_url = $this->_oid->identity;
				$this->em->persist($user);
				$this->em->flush();
			}
			return new \Zend_Auth_Result(\Zend_Auth_Result::SUCCESS, $user);
		} else {			
			return new \Zend_Auth_Result(\Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, $this->_oid);
		}
	}
}