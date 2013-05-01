<?php

namespace SelfServiceTest\Service;

use SelfService\Entity\User;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class UserServiceTest extends AbstractHttpControllerTestCase {

  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../config/application.config.php'
    );
    parent::setUp();
    $serviceManager = $this->getApplicationServiceLocator();

    // Initialize the schema.. Maybe I should register a module for clearing the schema/data
    // and/or loading mock test data
    $em = $serviceManager->get('doctrine.entitymanager.orm_default');
    $cli = new \Symfony\Component\Console\Application("PHPUnit Bootstrap", 1);
    $cli->setAutoExit(false);
    $helperSet = $cli->getHelperSet();
    $helperSet->set(new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em), 'em');
    $cli->addCommands(array(new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand()));
    $cli->run(
      new \Symfony\Component\Console\Input\ArrayInput(array('orm:schema-tool:create')),
      new \Symfony\Component\Console\Output\NullOutput()
    );
  }

  public function testCanGetAllUsers() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, count($userService->findAll()));

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $em->persist($newUser);
    $em->flush();

    $this->assertEquals(1, count($userService->findAll()));
  }

  public function testCanAuthorizeExistingUserByEmail() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, count($userService->findAll()));

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $em->persist($newUser);
    $em->flush();

    $users = $userService->findAll();

    $this->assertEquals(1, count($users));
    $this->assertFalse($users[0]->authorized, "User was authorized before authorize action was taken");
    $userService->authorizeByEmail("foo@bar.baz");

    $users = $userService->findAll();
    $this->assertTrue($users[0]->authorized, "User was not authorized after authorize action was taken");
  }

  public function testCanAuthorizeExistingUserByUrlEncodedEmail() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, count($userService->findAll()));

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $em->persist($newUser);
    $em->flush();

    $users = $userService->findAll();

    $this->assertEquals(1, count($users));
    $this->assertFalse($users[0]->authorized, "User was authorized before authorize action was taken");
    $userService->authorizeByEmail(urlencode("foo@bar.baz"));

    $users = $userService->findAll();
    $this->assertTrue($users[0]->authorized, "User was not authorized after authorize action was taken");
  }

  public function testCanPreauthorizeNonExistingUserByEmail() {
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, count($userService->findAll()));
    $userService->authorizeByEmail("foo@bar.baz");

    $users = $userService->findAll();
    $this->assertEquals(1, count($users));
    $this->assertTrue($users[0]->authorized, "User was not authorized after authorize action was taken");
  }

  public function testCanDeauthorizeExistingUserByEmail() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $userService = $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');

    $this->assertEquals(0, count($userService->findAll()));

    $newUser = new User();
    $newUser->email = "foo@bar.baz";
    $newUser->authorized = true;
    $em->persist($newUser);
    $em->flush();

    $users = $userService->findAll();

    $this->assertEquals(1, count($users));
    $this->assertTrue($users[0]->authorized, "User was deauthorized before deauthorize action was taken");
    $userService->deauthorizeByEmail("foo@bar.baz");

    $users = $userService->findAll();
    $this->assertFalse($users[0]->authorized, "User was not deauthorized after deauthorize action was taken");
  }

}