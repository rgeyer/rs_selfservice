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

namespace SelfService\Zend\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use SelfService\Entity\User;

class GoogleAuthAdapter implements AdapterInterface {
	/**
	 * @var \LightOpenID
	 */
	protected $_oid;

	protected $em;

  public function getPrincipalName() {
    $attributes = $this->_oid->getAttributes();
    return $attributes['namePerson/first'] . ' ' . $attributes['namePerson/last'];
  }

  public function getPrincipalEmail() {
    $attributes = $this->_oid->getAttributes();
    return $attributes['contact/email'];
  }

	public function __construct($entityManager, $hostname) {
		$this->_oid = new \LightOpenID($hostname);
    $this->_oid->required = array('namePerson/first', 'namePerson/last', 'contact/email');
		$this->em = $entityManager;
	}

	/**
	 * @return The string value of LightOpenID->mode
	 */
	public function getOidMode() {
		return $this->_oid->mode;
	}

	public function redirectUrlForGoogleAuth() {
		$this->_oid->__set('identity', 'https://www.google.com/accounts/o8/id');
		return $this->_oid->authUrl();
	}

	public function authenticate() {
		if($this->_oid->mode == 'cancel') {
			return new Result(Result::FAILURE, $this->_oid);
		} elseif ($this->_oid->validate()) {
      $query = $this->em->createQuery("select u from SelfService\Entity\User u where u.oid_url = :oid_url or u.email = :email");
      $query->setParameters(
        array(
          'oid_url' => $this->_oid->__get('identity'),
          'email' => $this->getPrincipalEmail()
        )
      );
			$user = $query->getResult();
			if(!$user) {
				$user = new User();
			} else {
        $user = array_pop($user);
      }
      $user->oid_url = $this->_oid->__get('identity');
      $user->email = $this->getPrincipalEmail();
      $user->name = $this->getPrincipalName();
      $this->em->persist($user);
      $this->em->flush();
			return new Result(Result::SUCCESS, $user);
		} else {
			return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->_oid);
		}
	}

}