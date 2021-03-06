<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SelfService;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
  public function onBootstrap(MvcEvent $e)
  {
    date_default_timezone_set('UTC');
    $e->getApplication()->getServiceManager()->get('translator');
    $eventManager        = $e->getApplication()->getEventManager();
    $moduleRouteListener = new ModuleRouteListener();
    $moduleRouteListener->attach($eventManager);

    $eventManager->attach('route', array($this, 'routeEventHandler'));
  }

  public function getConfig()
  {
    return include __DIR__ . '/config/module.config.php';
  }

  public function getAutoloaderConfig()
  {
    return array(
      'Zend\Loader\StandardAutoloader' => array(
        'namespaces' => array(
          __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
        ),
      ),
    );
  }

  public function routeEventHandler(MvcEvent $event) {
    $app = $event->getApplication();
    $sm = $app->getServiceManager();

    $router = $sm->get('router');
    $request = $sm->get('request');

    $matchedRoute = $router->match($request);
    if(null !== $matchedRoute) {
      $sm->get('ControllerPluginManager')->get('GoogleAuthPlugin')
        ->doAuthenticate($event, $sm);
    }
  }

  public function getViewHelperConfig()
  {
    return array(
      'factories' => array(
        'ControllerName' => function ($sm) {
          $match = $sm->getServiceLocator()->get('application')->getMvcEvent()->getRouteMatch();
          $viewHelper = new \SelfService\View\Helper\ControllerName($match);
          return $viewHelper;
        },
      ),
    );
  }
}
