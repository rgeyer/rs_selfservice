<?php

namespace SelfServiceTest\Controller\Api;

use Zend\Http\Request;
use Zend\Http\Response;
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

  /**
   * @return \SelfService\Service\Entity\UserService
   */
  protected function getUserService() {
    return $this->getApplicationServiceLocator()->get('SelfService\Service\Entity\UserService');
  }

  public function testCreateCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user', Request::METHOD_POST, array('email' => 'foo@bar.baz'));

    $this->assertActionName('create');
    $this->assertControllerName('selfservice\controller\api\user');
    $this->assertResponseStatusCode(201);
    $this->assertHasResponseHeader('Location');
    $this->assertRegExp(',api/user/[0-9a-z]+,', strval($this->getResponse()));
  }

  public function testCreateReturns400WhenEmailIsMissing() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user', Request::METHOD_POST);
    $this->assertResponseStatusCode(400);
  }

  public function testCreateHashesPlaintextPassword() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user', Request::METHOD_POST,
      array('email' => 'foo@bar.baz', 'password' => 'password'));
    $users = $this->getUserService()->findAll();
    $user = $users->getNext();
    $this->assertNotEquals('password', $user->password);
    $this->assertRegExp('/[0-9a-z]{32}/', $user->password);
  }

  public function testDeleteCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $user = $this->getUserService()->create(array());
    $this->dispatch("/api/user/$user->id", Request::METHOD_DELETE);

    $this->assertResponseStatusCode(200);
  }

  public function testDeleteReturns404ForNonExistentUser() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user/abc123', Request::METHOD_DELETE);

    $this->assertResponseStatusCode(404);
  }

  public function testGetCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user/abc123', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testGetListCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user', Request::METHOD_GET);

    $this->assertResponseStatusCode(501);
  }

  public function testUpdateCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user/abc123', Request::METHOD_PUT);

    $this->assertResponseStatusCode(501);
  }

  public function testAuthorizeCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $userService = $this->getMock('\SelfService\Service\Entity\UserService');
    $userService->expects($this->once())
      ->method('authorize');
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\UserService', $userService);
    $this->dispatch('/api/user/abc123/authorize', Request::METHOD_POST);

    $this->assertActionName('authorize');
    $this->assertControllerName('selfservice\controller\api\user');
    $this->assertResponseStatusCode(200);
  }

  public function testAuthorizeReturns405OnNonPostMethod() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user/abc123/authorize', Request::METHOD_PUT);

    $this->assertResponseStatusCode(405);
  }

  public function testAuthorizeReturns404ForNonExistentUser() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user/abc123/authorize', Request::METHOD_POST);

    $this->assertResponseStatusCode(404);
  }

  public function testDeauthorizeCanBeAccessed() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $userService = $this->getMock('\SelfService\Service\Entity\UserService');
    $userService->expects($this->once())
      ->method('deauthorize');
    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\UserService', $userService);
    $this->dispatch('/api/user/abc123/deauthorize', Request::METHOD_POST);

    $this->assertActionName('deauthorize');
    $this->assertControllerName('selfservice\controller\api\user');
    $this->assertResponseStatusCode(200);
  }

  public function testDeauthorizeReturns405OnNonPostMethod() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user/abc123/authorize', Request::METHOD_PUT);

    $this->assertResponseStatusCode(405);
  }

  public function testDeauthorizeReturns404ForNonExistentUser() {
    \SelfServiceTest\Helpers::disableAuthenticationAndAuthorization($this->getApplicationServiceLocator());
    $this->dispatch('/api/user/abc123/authorize', Request::METHOD_POST);

    $this->assertResponseStatusCode(404);
  }
}