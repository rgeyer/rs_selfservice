<?php

namespace SelfServiceTest\Controller\Http;

use SelfService\Document\User;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class UserControllerTest extends AbstractHttpControllerTestCase {
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

  public function testIndexActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/user');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(200);
  }

  public function testIndexActionShowsCorrectActionsForUsers() {
    $em = $this->getApplicationServiceLocator()->get('doctrine.documentmanager.odm_default');
    $user1 = new User();
    $user1->email = "user1@domain.com";
    $user1->authorized = false;
    $em->persist($user1);

    $user2 = new User();
    $user2->email = "user2@domain.com";
    $user2->authorized = true;
    $em->persist($user2);

    $em->flush();
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/user');

    $this->assertActionName('index');
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(200);
    $this->assertXpathQueryCount("//tbody/tr", 2);
    $this->assertXpathQueryCount("//img[@class='action_deauthorize']", 1);
    $this->assertXpathQueryCount("//img[@class='action_authorize']", 1);
    $this->assertXpathQueryCount("//a[@href='/api/user/$user1->id/authorize']", 1);
    $this->assertXpathQueryCount("//a[@href='/api/user/$user1->id/deauthorize']", 0);
    $this->assertXpathQueryCount("//a[@href='/api/user/$user2->id/authorize']", 0);
    $this->assertXpathQueryCount("//a[@href='/api/user/$user2->id/deauthorize']", 1);
  }

  public function testUnauthorizedActionCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/user/unauthorized');

    $this->assertActionName('unauthorized');
    $this->assertControllerName('selfservice\controller\user');
    $this->assertResponseStatusCode(200);
  }
}