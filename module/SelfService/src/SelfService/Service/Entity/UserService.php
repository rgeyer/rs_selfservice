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

namespace SelfService\Service\Entity;

use SelfService\Entity\User;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class UserService implements ServiceLocatorAwareInterface {

  /**
   * @var string The name of the entity class for this service
   */
  protected $entityClass = 'SelfService\Entity\User';

  /**
   * @var ServiceLocatorInterface
   */
  protected $serviceLocator;

  /**
   * @return \Doctrine\ORM\EntityManager
   */
  protected function getEntityManager() {
    return $this->serviceLocator->get('doctrine.entitymanager.orm_default');
  }

  /**
   * @return ServiceLocatorInterface
   */
  public function getServiceLocator() {
    return $this->serviceLocator;
  }

  /**
   * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
   * @return void
   */
  public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
    $this->serviceLocator = $serviceLocator;
  }

  /**
   * @return User[] An array of all User entities
   */
  public function findAll() {
    $em = $this->getEntityManager();
    return $em->getRepository($this->entityClass)->findAll();
  }

  /**
   * @param $email Email address of the user to find
   * @return \SelfService\Entity\User|null
   */
  public function findByEmail($email) {
    $em = $this->getEntityManager();
    $users = $em->getRepository($this->entityClass)->findByEmail($email);
    # TODO: What if it returns more than one?
    if($users && count($users) == 1) {
      return array_pop($users);
    } else {
      return null;
    }
  }

  /**
   * @param $emailEmail address of the user to authorize
   * @return void
   */
  public function authorizeByEmail($email) {
    $em = $this->getEntityManager();
    $user = $this->findByEmail($email);
    if(!$user) {
      $user = new User();
      $user->email = $email;
    }
    $user->authorized = true;
    $em->persist($user);
    $em->flush();
  }

  /**
   * @param $emailEmail address of the user to deauthorize
   * @return void
   */
  public function deauthorizeByEmail($email) {
    $em = $this->getEntityManager();
    $user = $this->findByEmail($email);
    if($user) {
      $user->authorized = false;
      $em->persist($user);
      $em->flush();
    }
  }

}