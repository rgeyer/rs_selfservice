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

use Doctrine\ODM\MongoDB\LockMode;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use SelfService\Document\Exception\NotFoundException;

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
   * @return \Doctrine\ODM\MongoDB\DocumentManager
   */
  protected function getDocumentManager() {
    return $this->serviceLocator->get('doctrine.documentmanager.odm_default');
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
   * @return \Doctrine\ODM\MongoDB\Cursor An array of all entities of the concrete type
   */
  public function findAll() {
    $dm = $this->getDocumentManager();
    return $dm->getRepository($this->entityClass)->findAll();
  }

  /**
   * @throws \SelfService\Document\Exception\NotFoundException When the specified document
   *  does not exist.
   * @param $id
   * @param int $lockMode
   * @param null $lockVersion
   * @return \Doctrine\ODM\MongoDB\The|object
   */
  public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
    $dm = $this->getDocumentManager();
    $doc = $dm->getRepository($this->entityClass)->find($id, $lockMode, $lockVersion);
    if(!$doc) {
      throw new NotFoundException($this->entityClass, $id);
    }
    return $doc;
  }

  /**
   * @param $id The ID of the entity to remove
   * @return void
   */
  public function remove($id) {
    $dm = $this->getDocumentManager();
    $dm->remove($this->find($id));
    $dm->flush();
  }

  /**
   * @param $params An associative array of properties to set on the newly created entity.
   * @return The newly created entity
   */
  public function create(array $params) {
    $entity = new $this->entityClass;
    $dm = $this->getDocumentManager();
    foreach($params as $paramname => $param) {
      if(property_exists($entity, $paramname)) {
        $entity->{$paramname} = $param;
      }
    }
    $dm->persist($entity);
    $dm->flush();
    return $entity;
  }

  public function update($idOrOdm, array $params) {
    $entity = $idOrOdm;
    if(is_scalar($idOrOdm)) {
      $entity = $this->find($idOrOdm);
    }
    $dm = $this->getDocumentManager();
    foreach($params as $paramname => $param) {
      if(property_exists($entity, $paramname)) {
        $entity->{$paramname} = $param;
      }
    }
    $dm->persist($entity);
    $dm->flush();
    return $entity;
  }

  public function detach($doc) {
    $this->getDocumentManager()->detach($doc);
    foreach(get_object_vars($doc) as $key => $val) {
      if(strpos(get_class($val), "SelfService\Document") === 0) {
        $this->detach($val);
      }
    }
  }

  /**
   * @return \Doctrine\ODM\MongoDB\Query\Builder
   */
  public function getQueryBuilder() {
    return $this->getDocumentManager()->createQueryBuilder($this->entityClass);
  }

}