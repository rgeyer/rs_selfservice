<?php

require_once __DIR__."/../../mock/ZendAuthAdapterMock.php";

class AdminControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

  public function setUp()
  {
    $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
    parent::setUp();
  }

  public function testIndexAction()
  {
    $auth = Zend_Auth::getInstance();
    $auth->authenticate(new ZendAuthAdaptherMock());
    $params = array('action' => 'index', 'controller' => 'Admin', 'module' => 'default');
    $urlParams = $this->urlizeOptions($params);
    $url = $this->url($urlParams);
    $this->dispatch($url);

    // assertions
    $this->assertModule($urlParams['module']);
    $this->assertController($urlParams['controller']);
    $this->assertAction($urlParams['action']);
    $this->assertQueryContentContains(
      'div#view-content p',
      'View script for controller <b>' . $params['controller'] . '</b> and script/action name <b>' . $params['action'] . '</b>'
    );
  }


}



