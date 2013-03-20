<?php

namespace SelfServiceTest;

use SelfService\Entity\User;
use Zend\Authentication\Result;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\ServiceManager;

class Helpers {

  public static function disableAuthenticationAndAuthorization(ServiceManager $sm) {
    $test = new ConcreteGuzzleTestCase();
    $plugin = $test->getMock('SelfService\Controller\Plugin\GoogleAuthPlugin');

    $controller_plugin_manager = $sm->get("ControllerPluginManager");
    $controller_plugin_manager->setAllowOverride(true);
    $controller_plugin_manager->setService('GoogleAuthPlugin', $plugin);
  }

  public static function authenticateAsAdmin(ServiceManager $sm) {
    # TODO: Auth in such a way that I don't clobber the entity manager for all
    # subsequent use.
    $test = new ConcreteGuzzleTestCase();

    $user = new User();
    $user->email = 'foo@bar.baz';
    $user->oid_url = 'oid_url';

    $userRepo = $test->getMockBuilder('Doctrine\ORM\EntityRepository')
      ->disableOriginalConstructor()
      ->getMock();
    $userRepo->expects($test->any())
      ->method('findOneBy')
      ->will($test->returnValue($user));

    $em = $test->getMockBuilder('Doctrine\ORM\EntityManager')
      ->disableOriginalConstructor()
      ->getMock();
    $em->expects($test->any())
      ->method('getRepository')
      ->with('SelfService\Entity\User')
      ->will($test->returnValue($userRepo));

    $sm->setAllowOverride(true);
    $sm->setService('doctrine.entitymanager.orm_default', $em);

    $adapter = $test->getMock('Zend\Authentication\Adapter\AdapterInterface');
    $adapter->expects($test->once())
      ->method('authenticate')
      ->will($test->returnValue(new Result(Result::SUCCESS, $user)));
    $auth = new AuthenticationService();
    $auth->authenticate($adapter);
  }

}

class ConcreteGuzzleTestCase extends \Guzzle\Tests\GuzzleTestCase {}