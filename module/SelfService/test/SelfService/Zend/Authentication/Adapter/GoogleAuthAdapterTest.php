<?php

namespace SelfServiceTest\Zend\Authentication\Adapter;

use Zend\Authentication\Result;
use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class GoogleAuthAdapterTest extends AbstractConsoleControllerTestCase {
  public function setUp() {
    $this->setApplicationConfig(
      include __DIR__ . '/../../../../../../../config/application.config.php'
    );
    parent::setUp();

    $serviceManager = $this->getApplicationServiceLocator();
}

  /**
   * @return \SelfService\Zend\Authentication\Adapter\GoogleAuthAdapter
   */
  protected function getAuthenticationAdapter() {
    return $this->getApplicationServiceLocator()->get('AuthenticationAdapter');
  }

  /**
   * @expectedException Exception
   * @expectedExceptionMessage The principal is not valid. Make sure the user has authenticated through a browser
   */
  public function testGetPrincipleNameThrowsExceptionWhenNotValidated() {
    $lightMock = $this->getMock('\LightOpenID');

    $lightMock->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(false));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('LightOpenID', $lightMock);

    $authAdapter = $this->getAuthenticationAdapter();
    $authAdapter->getPrincipalName();
  }

  public function testGetPrincipleNameConcatenantesFirstAndLast() {
    $lightMock = $this->getMock('\LightOpenID');

    $lightMock->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(true));

    $lightMock->expects($this->once())
      ->method('getAttributes')
      ->will($this->returnValue(array('namePerson/first' => 'first', 'namePerson/last' => 'last')));


    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('LightOpenID', $lightMock);

    $authAdapter = $this->getAuthenticationAdapter();
    $this->assertEquals('first last', $authAdapter->getPrincipalName());
  }

  /**
   * @expectedException Exception
   * @expectedExceptionMessage The principal is not valid. Make sure the user has authenticated through a browser
   */
  public function testGetPrincipalEmailThrowsExceptionWhenNotValidated() {
    $lightMock = $this->getMock('\LightOpenID');

    $lightMock->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(false));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('LightOpenID', $lightMock);

    $authAdapter = $this->getAuthenticationAdapter();
    $authAdapter->getPrincipalEmail();
  }

  public function testCanGetPrincipalEmail() {
    $lightMock = $this->getMock('\LightOpenID');

    $lightMock->expects($this->once())
      ->method('validate')
      ->will($this->returnValue(true));

    $lightMock->expects($this->once())
      ->method('getAttributes')
      ->will($this->returnValue(array('contact/email' => 'foo@bar.baz')));


    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('LightOpenID', $lightMock);

    $authAdapter = $this->getAuthenticationAdapter();
    $this->assertEquals('foo@bar.baz', $authAdapter->getPrincipalEmail());
  }

  public function testOpenIdUrlRequestsEmail() {
    $authAdapter = $this->getAuthenticationAdapter();
    $oauthUrl = $authAdapter->getOpenIdUrl();
    $this->assertContains('openid.ax.type.contact_email=http%3A%2F%2Faxschema.org%2Fcontact%2Femail', $oauthUrl);
  }

  public function testAuthenticateReturnsFailureResultOnCancel() {
    $lightMock = $this->getMock('\LightOpenID');

    $lightMock->expects($this->once())
      ->method('__get')
      ->with('mode')
      ->will($this->returnValue('cancel'));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('LightOpenID', $lightMock);

    $authAdapter = $this->getAuthenticationAdapter();
    $result = $authAdapter->authenticate();
    $this->assertInstanceOf('Zend\Authentication\Result', $result);
    $this->assertEquals(Result::FAILURE, $result->getCode());
  }

  public function testAuthenticateCreatesUserWhenNoneExists() {
    $queryMock = $this->getMockBuilder('Doctrine\MongoDB\Query\Query')
      ->disableOriginalConstructor()
      ->getMock();
    $queryMock->expects($this->once())
      ->method('getSingleResult')
      ->will($this->returnValue(null));
    $queryBuilderMock = $this->getMockBuilder('Doctrine\ODM\MongoDB\Query\Builder')
      ->disableOriginalConstructor()->getMock();
    $queryBuilderMock->expects($this->once())
      ->method('where')
      ->will($this->returnValue($queryBuilderMock));
    $queryBuilderMock->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($queryMock));
    $userServiceMock = $this->getMock('SelfService\Service\Entity\UserService');
    $userServiceMock->expects($this->once())
      ->method('getQueryBuilder')
      ->will($this->returnValue($queryBuilderMock));
    $userServiceMock->expects($this->once())
      ->method('create');
    $lightMock = $this->getMock('\LightOpenID');

    $lightMock->expects($this->any())
      ->method('validate')
      ->will($this->returnValue(true));

    $lightMock->expects($this->any())
      ->method('getAttributes')
      ->will($this->returnValue(array(
                                     'contact/email' => 'foo@bar.baz',
                                     'namePerson/first' => 'first',
                                     'namePerson/last' => 'last')));

    $lightMock->expects($this->exactly(2))
      ->method('__get')
      ->will($this->returnValueMap(array('identity' => 'oid_url', 'mode' => 'mode')));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('LightOpenID', $lightMock);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\UserService', $userServiceMock);

    $authAdapter = $this->getAuthenticationAdapter();
    $authAdapter->authenticate();
  }

  public function testAuthenticateUpdatesUserWhenExists() {
    $user = new \SelfService\Document\User();
    $queryMock = $this->getMockBuilder('Doctrine\MongoDB\Query\Query')
      ->disableOriginalConstructor()
      ->getMock();
    $queryMock->expects($this->once())
      ->method('getSingleResult')
      ->will($this->returnValue($user));
    $queryBuilderMock = $this->getMockBuilder('Doctrine\ODM\MongoDB\Query\Builder')
      ->disableOriginalConstructor()->getMock();
    $queryBuilderMock->expects($this->once())
      ->method('where')
      ->will($this->returnValue($queryBuilderMock));
    $queryBuilderMock->expects($this->once())
      ->method('getQuery')
      ->will($this->returnValue($queryMock));
    $userServiceMock = $this->getMock('SelfService\Service\Entity\UserService');
    $userServiceMock->expects($this->once())
      ->method('getQueryBuilder')
      ->will($this->returnValue($queryBuilderMock));
    $userServiceMock->expects($this->once())
      ->method('update');
    $lightMock = $this->getMock('\LightOpenID');

    $lightMock->expects($this->any())
      ->method('validate')
      ->will($this->returnValue(true));

    $lightMock->expects($this->any())
      ->method('getAttributes')
      ->will($this->returnValue(array(
                                     'contact/email' => 'foo@bar.baz',
                                     'namePerson/first' => 'first',
                                     'namePerson/last' => 'last')));

    $lightMock->expects($this->exactly(2))
      ->method('__get')
      ->will($this->returnValueMap(array('identity' => 'oid_url', 'mode' => 'mode')));

    $this->getApplicationServiceLocator()->setAllowOverride(true);
    $this->getApplicationServiceLocator()->setService('LightOpenID', $lightMock);
    $this->getApplicationServiceLocator()->setService('SelfService\Service\Entity\UserService', $userServiceMock);

    $authAdapter = $this->getAuthenticationAdapter();
    $authAdapter->authenticate();
  }

}