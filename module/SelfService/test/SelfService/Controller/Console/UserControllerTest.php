<?php

namespace SelfServiceTest\Controller\Console;

use SelfService\Entity\User;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class UserControllerTest extends AbstractConsoleControllerTestCase {
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

  public function testIndexUserActionCanBeAccessed() {
    $this->dispatch('users list');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(0);
  }

  public function testIndexUserActionListsAllUsersWithEmailAndAuthorizedStatus() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.entitymanager.orm_default');
    $user1 = new User();
    $user1->email = "user1@domain.com";
    $user1->authorized = false;
    $em->persist($user1);

    $user2 = new User();
    $user2->email = "user2@domain.com";
    $user2->authorized = true;
    $em->persist($user2);

    $em->flush();

    $consolemock = $this->getMock('Zend\Console\Adapter\AdapterInterface');
    $consolemock->expects($this->exactly(2))->method('writeLine');
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('console', $consolemock);

    $this->dispatch('users list');
  }

  public function testAuthorizeUserActionCanBeAccessed() {
    $userservicemock = $this->getMock('SelfService\Service\Entity\UserService');
    $userservicemock->expects($this->once())
      ->method('authorizeByEmail')
      ->with('foo@bar.baz');

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\UserService',$userservicemock);
    $this->dispatch('users authorize foo@bar.baz');

    $this->assertActionName('authorize');
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(0);
  }

  public function testDeuthorizeUserActionCanBeAccessed() {
    $userservicemock = $this->getMock('SelfService\Service\Entity\UserService');
    $userservicemock->expects($this->once())
      ->method('deauthorizeByEmail')
      ->with('foo@bar.baz');

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\UserService',$userservicemock);
    $this->dispatch('users deauthorize foo@bar.baz');

    $this->assertActionName('deauthorize');
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(0);
  }
}