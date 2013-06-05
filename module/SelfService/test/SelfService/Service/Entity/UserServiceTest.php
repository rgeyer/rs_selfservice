<?php

namespace SelfServiceTest\Service;

use SelfService\Document\User;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class UserServiceTest extends AbstractHttpControllerTestCase {

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();

    $cli = $this->getApplicationServiceLocator()->get('doctrine.cli');
    $cli->setAutoExit(false);

    $cli->run(
      new \Symfony\Component\Console\Input\ArrayInput(array('odm:schema:drop')),
      new \Symfony\Component\Console\Output\NullOutput()
    );

    $cli->run(
      new \Symfony\Component\Console\Input\ArrayInput(array('odm:schema:create')),
      new \Symfony\Component\Console\Output\NullOutput()
    );
  }

  /**
   * @return \SelfService\Service\Entity\UserService
   */
  protected function getUserEntityService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');
  }

  public function testCreateReturnsPersistedDocumentWithId() {
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');
    $user = $userService->create(array());
    $this->assertNotNull($user->id);
  }

  public function testCanGetAllUsers() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, $userService->findAll()->count());

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $em->persist($newUser);
    $em->flush();

    $this->assertEquals(1, $userService->findAll()->count());
  }

  public function testCanAuthorizeExistingUserByEmail() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, $userService->findAll()->count());

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $em->persist($newUser);
    $em->flush();

    $users = $userService->findAll();

    $this->assertEquals(1, $users->count());
    $user = $users->getNext();
    $this->assertFalse($user->authorized, "User was authorized before authorize action was taken");
    $userService->authorizeByEmail("foo@bar.baz");

    $users = $userService->findAll();
    $user = $users->getNext();
    $this->assertTrue($user->authorized, "User was not authorized after authorize action was taken");
  }

  public function testCanAuthorizeExistingUserByUrlEncodedEmail() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, $userService->findAll()->count());

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $em->persist($newUser);
    $em->flush();

    $users = $userService->findAll();

    $this->assertEquals(1, $users->count());
    $this->assertFalse($users->getNext()->authorized, "User was authorized before authorize action was taken");
    $userService->authorizeByEmail(urlencode("foo@bar.baz"));

    $users = $userService->findAll();
    $this->assertTrue($users->getNext()->authorized, "User was not authorized after authorize action was taken");
  }

  public function testCanPreauthorizeNonExistingUserByEmail() {
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, $userService->findAll()->count());
    $userService->authorizeByEmail("foo@bar.baz");

    $users = $userService->findAll();
    $this->assertEquals(1, $users->count());
    $this->assertTrue($users->getNext()->authorized, "User was not authorized after authorize action was taken");
  }

  public function testCanDeauthorizeExistingUserByEmail() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, $userService->findAll()->count());

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $newUser->authorized = true;
    $em->persist($newUser);
    $em->flush();

    $users = $userService->findAll();

    $this->assertEquals(1, $users->count());
    $this->assertTrue($users->getNext()->authorized, "User was deauthorized before deauthorize action was taken");
    $userService->deauthorizeByEmail("foo@bar.baz");

    $users = $userService->findAll();
    $this->assertFalse($users->getNext()->authorized, "User was not deauthorized after deauthorize action was taken");
  }

  public function testCanFindByEmail() {
    $userService = $this->getUserEntityService();

    $this->assertEquals(0, $userService->findAll()->count());

    $userService->create(array('email' => 'foo@bar.baz'));

    $user = $userService->findByEmail('foo@bar.baz');

    $this->assertNotNull($user);
    $this->assertInstanceOf('SelfService\Document\User', $user);
  }

  public function testCanFindByOidUrl() {
    $userService = $this->getUserEntityService();

    $this->assertEquals(0, $userService->findAll()->count());

    $userService->create(array('oid_url' => 'http://oid.url'));

    $user = $userService->findByOidUrl('http://oid.url');

    $this->assertNotNull($user);
    $this->assertInstanceOf('SelfService\Document\User', $user);
  }

}