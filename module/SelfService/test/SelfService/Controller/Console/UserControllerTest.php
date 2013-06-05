<?php

namespace SelfServiceTest\Controller\Console;

use SelfService\Document\User;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class UserControllerTest extends AbstractConsoleControllerTestCase {
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

  public function testIndexUserActionCanBeAccessed() {
    $this->dispatch('users list');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(0);
  }

  public function testIndexUserActionListsAllUsersWithEmailAndAuthorizedStatus() {
    $dm = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $user1 = new User();
    $user1->email = "user1@domain.com";
    $user1->authorized = false;
    $dm->persist($user1);

    $user2 = new User();
    $user2->email = "user2@domain.com";
    $user2->authorized = true;
    $dm->persist($user2);

    $dm->flush();

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