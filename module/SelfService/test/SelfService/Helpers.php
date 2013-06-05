<?php

namespace SelfServiceTest;

use SelfService\Document\User;
use Zend\Authentication\Result;
use Zend\ServiceManager\ServiceManager;

class Helpers {

  public static function disableAuthenticationAndAuthorization(ServiceManager $sm) {
    $test = new ConcreteGuzzleTestCase();
    $plugin = $test->getMock('SelfService\Controller\Plugin\GoogleAuthPlugin');

    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $standin_adapter);

    $controller_plugin_manager = $sm->get("ControllerPluginManager");
    $controller_plugin_manager->setAllowOverride(true);
    $controller_plugin_manager->setService('GoogleAuthPlugin', $plugin);
  }

  public static function authenticateAsAdmin(ServiceManager $sm) {
    # TODO: Auth in such a way that I don't clobber the entity manager for all
    # subsequent use.
    $test = new ConcreteGuzzleTestCase();

    $user = new User();
    $user->id = "abc123";
    $user->name = "foo bar";
    $user->email = 'foo@bar.baz';
    $user->oid_url = 'oid_url';
    $user->authorized = true;

    $standin_adapter = new \Zend\Cache\Storage\Adapter\Memory();
    $sm->setAllowOverride(true);
    $sm->setService('cache_storage_adapter', $standin_adapter);

    $userServiceMock = $test->getMock('SelfService\Service\Entity\UserService');
    $userServiceMock->expects($test->any())
      ->method('findByOidUrl')
      ->will($test->returnValue($user));
    $sm->setService('SelfService\Service\Entity\UserService', $userServiceMock);

    $adapter = $test->getMock('Zend\Authentication\Adapter\AdapterInterface');
    $adapter->expects($test->once())
      ->method('authenticate')
      ->will($test->returnValue(new Result(Result::SUCCESS, $user)));
    $auth = $sm->get('AuthenticationService');
    $auth->authenticate($adapter);
  }

  public static function authenticateAsUnauthorizedUser(ServiceManager $sm) {
    # TODO: Auth in such a way that I don't clobber the entity manager for all
    # subsequent use.
    $test = new ConcreteGuzzleTestCase();

    $user = new User();
    $user->id = "abc123";
    $user->name = "foo bar";
    $user->email = 'foo@bar.baz';
    $user->oid_url = 'oid_url';
    $user->authorized = false;

    $userServiceMock = $test->getMock('SelfService\Service\Entity\UserService');
    $userServiceMock->expects($test->any())
      ->method('findByOidUrl')
      ->will($test->returnValue($user));
    $sm->setService('SelfService\Service\Entity\UserService', $userServiceMock);

    $adapter = $test->getMock('Zend\Authentication\Adapter\AdapterInterface');
    $adapter->expects($test->once())
      ->method('authenticate')
      ->will($test->returnValue(new Result(Result::SUCCESS, $user)));
    $auth = $sm->get('AuthenticationService');
    $auth->authenticate($adapter);
  }

}

class ConcreteGuzzleTestCase extends \Guzzle\Tests\GuzzleTestCase {}