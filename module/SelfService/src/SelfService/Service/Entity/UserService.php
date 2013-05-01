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
use SelfService\Entity\User;
use Doctrine\ORM\EntityManager;

class UserService extends BaseEntityService {

  /**
   * @var string The name of the entity class for this service
   */
  protected $entityClass = 'SelfService\Entity\User';

  /**
   * @return \SelfService\Entity\User[] An array of all User entities
   */
  public function findAll() {
    return parent::findAll();
  }

  /**
   * @param $id
   * @param $lockMode
   * @param null $lockVersion
   * @return \SelfService\Entity\User
   */
  public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
    return parent::find($id, $lockMode, $lockVersion);
  }

  /**
   * @param $email Email address of the user to find
   * @return \SelfService\Entity\User|null
   */
  public function findByEmail($email) {
    $em = $this->getEntityManager();
    $email = urldecode($email);
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