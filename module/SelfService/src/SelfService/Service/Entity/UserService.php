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
use SelfService\Document\User;

class UserService extends BaseEntityService {

  /**
   * @var string The name of the entity class for this service
   */
  protected $entityClass = 'SelfService\Document\User';

  /**
   * @return \SelfService\Document\User[] An array of all User entities
   */
  public function findAll() {
    return parent::findAll();
  }

  /**
   * @param $id
   * @param $lockMode
   * @param null $lockVersion
   * @return \SelfService\Document\User
   */
  public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
    return parent::find($id, $lockMode, $lockVersion);
  }

  /**
   * @param $oid_url Users oid url
   * @return \SelfService\Document\User|null
   */
  public function findByOidUrl($oid_url) {
    $dm = $this->getDocumentManager();
    $oid_url = urldecode($oid_url);
    $users = $dm->getRepository($this->entityClass)->findBy(array('oid_url' => $oid_url));
    # TODO: What if it returns more than one?
    if($users && $users->count() == 1) {
      return $users->getNext();
    } else {
      return null;
    }
  }

  /**
   * @param $email Email address of the user to find
   * @return \SelfService\Document\User|null
   */
  public function findByEmail($email) {
    $dm = $this->getDocumentManager();
    $email = urldecode($email);
    $users = $dm->getRepository($this->entityClass)->findByEmail($email);
    # TODO: What if it returns more than one?
    if($users && $users->count() == 1) {
      return $users->getNext();
    } else {
      return null;
    }
  }

  /**
   * @param $oid The oid_url to search
   * @param $email The email address to search
   * @return \SelfService\Document\User|null
   */
  public function findByOidOrEmail($oid, $email) {
    $qb = $this->getQueryBuilder();
    $qb->addOr($qb->expr()->field('oid_url')->equals($oid));
    $qb->addOr($qb->expr()->field('email')->equals($email));
    $query = $qb->getQuery();
    $user = $query->getSingleResult();
    return $user;
  }

  /**
   * @throws \SelfService\Document\Exception\NotFoundException When the specified document
   *  does not exist.
   * @param String $id DB ID of the user to authorize
   * @return void
   */
  public function authorize($id) {
    $dm = $this->getDocumentManager();
    $user = $this->find($id);
    $user->authorized = true;
    $dm->persist($user);
    $dm->flush();
  }

  /**
   * @param String $email Email address of the user to authorize
   * @return void
   */
  public function authorizeByEmail($email) {
    $dm = $this->getDocumentManager();
    $user = $this->findByEmail($email);
    if(!$user) {
      $user = new User();
      $user->email = $email;
    }
    $user->authorized = true;
    $dm->persist($user);
    $dm->flush();
  }

  /**
   * @param String $email Email address of the user to deauthorize
   * @return void
   */
  public function deauthorizeByEmail($email) {
    $dm = $this->getDocumentManager();
    $user = $this->findByEmail($email);
    if($user) {
      $user->authorized = false;
      $dm->persist($user);
      $dm->flush();
    }
  }

  /**
   * @throws \SelfService\Document\Exception\NotFoundException When the specified document
   *  does not exist.
   * @param String $id DB ID of the user to deauthorize
   * @return void
   */
  public function deauthorize($id) {
    $dm = $this->getDocumentManager();
    $user = $this->find($id);
    $user->authorized = false;
    $dm->persist($user);
    $dm->flush();
  }

}