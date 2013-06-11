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


use SelfService\Document\User;
use Zend\Authentication\Result;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Authentication\Adapter\AdapterInterface;

class GoogleAuthAdapter implements AdapterInterface {

  /**
   * @var \Zend\ServiceManager\ServiceLocatorInterface
   */
  protected $serviceLocator;

  /**
   * @return \Zend\ServiceManager\ServiceLocatorInterface
   */
  public function getServiceLocator() {
    return $this->serviceLocator;
  }

	/**
	 * @var \LightOpenID
	 */
	protected $_oid;

  /**
   * @return \SelfService\Service\Entity\UserService
   */
  protected function getUserEntityService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\UserService');
  }

  /**
   * Returns the principal first name and last name concatenated
   * TODO: Probably should throw a more specific exception
   * @throws \Exception if the principal has not been authenticated or is not valid
   * @return string Principal first and last name concatenated
   */
  public function getPrincipalName() {
    if(count($this->_oid->getAttributes()) == 0) {
      throw new \Exception("The principal is not valid. Make sure the user has authenticated through a browser");
    }
    $attributes = $this->_oid->getAttributes();
    return $attributes['namePerson/first'] . ' ' . $attributes['namePerson/last'];
  }

  /**
   * Returns the principal email address
   * TODO: Probably should throw a more specific exception
   * @throws \Exception if the principal has not been authenticated or is not valid
   * @return string Principal email address
   */
  public function getPrincipalEmail() {
    if(count($this->_oid->getAttributes()) == 0) {
      throw new \Exception("The principal is not valid. Make sure the user has authenticated through a browser");
    }
    $attributes = $this->_oid->getAttributes();
    return $attributes['contact/email'];
  }

	public function __construct(ServiceLocatorInterface $serviceLocator) {
    $this->serviceLocator = $serviceLocator;
		$this->_oid = $this->getServiceLocator()->get('LightOpenID');
    $this->_oid->required = array('namePerson/first', 'namePerson/last', 'contact/email');
	}

	/**
	 * @return string The string value of LightOpenID->mode
	 */
	public function getOidMode() {
		return $this->_oid->__get('mode');
	}

  /**
   * @return \String The OpenID URL the user should browse to in order to get authenticated.
   */
	public function getOpenIdUrl() {
		$this->_oid->__set('identity', 'https://www.google.com/accounts/o8/id');
		return $this->_oid->authUrl();
	}

  /**
   * @return \Zend\Authentication\Result
   */
	public function authenticate() {
		if($this->getOidMode() == 'cancel') {
			return new Result(Result::FAILURE, $this->_oid);
		} elseif ($this->_oid->validate()) {
      $identity = $this->_oid->__get('identity');
      $query = $this->getUserEntityService()->getQueryBuilder()
        ->where(
          sprintf('u.oid_url = "%s" or u.email = %s',
                  $identity,
                  $this->getPrincipalEmail()
          )
        )
        ->getQuery();
      $user_params = array(
        'oid_url' => $identity,
        'email' => $this->getPrincipalEmail(),
        'name' => $this->getPrincipalName()
      );
			$user = $query->getSingleResult();
			if(!$user) {
				$user = $this->getUserEntityService()->create($user_params);
			} else {
        $this->getUserEntityService()->update($user, $user_params);
      }
			return new Result(Result::SUCCESS, $user);
		} else {
			return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->_oid);
		}
	}

}