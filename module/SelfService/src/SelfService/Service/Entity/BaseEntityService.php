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

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class BaseEntityService implements ServiceLocatorAwareInterface {

  /**
   * @var string The name of the entity class for this service
   */
  protected $entityClass;

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
   * @return array An array of all entities of the concrete type
   */
  public function findAll() {
    $em = $this->getEntityManager();
    return $em->getRepository($this->entityClass)->findAll();
  }

  /**
   * @param $id The ID of the entity to return
   * @param int $lockMode
   * @param null $lockVersion
   * @return \Doctrine\ORM\The|object
   */
  public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
    $em = $this->getEntityManager();
    return $em->getRepository($this->entityClass)->find($id, $lockMode, $lockVersion);
  }

}