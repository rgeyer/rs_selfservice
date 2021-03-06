<?php
/*
Copyright (c) 2013 Ryan J. Geyer <me@ryangeyer.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SelfService\Controller\Plugin;

use Zend\Mvc\MvcEvent;
use Zend\Session\Container;
use Zend\Console\Request as ConsoleRequest;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceLocatorInterface;

class GoogleAuthPlugin extends AbstractPlugin {

  /**
   * @return \SelfService\Service\Entity\UserService
   */
  protected function getUserEntityService($serviceManager) {
    return $serviceManager->get('SelfService\Service\Entity\UserService');
  }

  protected function redirect(MvcEvent $event, array $options, array $params = array()) {
    $url = $event->getRouter()->assemble($params, $options);
    $response = $event->getResponse();
    $response->getHeaders()->addHeaderLine('Location', $url);
    $response->setStatusCode(302);
    $response->sendHeaders();
    $event->stopPropagation(true);
  }

	public function doAuthenticate(MvcEvent $event, ServiceLocatorInterface $serviceManager) {
    # Console users are always authorized
    if ($event->getRequest() instanceof ConsoleRequest) {
      return;
    }

    $routematch = $event->getRouteMatch();
		$controller = $routematch->getParam('controller');
    $controller = strtolower($controller);

    $action = $routematch->getParam('action');
    $action = strtolower($action);

    # TODO: Previously exluded API calls from requiring auth. Now we need to accept
    # an API login method that will subsequently allow authenticated and authorized
    # requests.
    #if(preg_match('/api/', $controller) == 1) {
    #  return;
    #}

		if ($controller == 'selfservice\controller\login') {
			return;
		}
		$redirect_to_auth = false;
		$auth = $serviceManager->get('AuthenticationService');
    $user = null;
		if(!$auth->hasIdentity()) {
			$redirect_to_auth = true;
		} else {
      $userService = $this->getUserEntityService($serviceManager);
      $user = $userService->findByOidUrl($auth->getIdentity()->oid_url);

			$redirect_to_auth = ($user == null);
		}

		if($redirect_to_auth) {
      # TODO: Implement the use of sessions better, and more safely
      # http://framework.zend.com/manual/2.1/en/modules/zend.session.manager.html
      $sess = new Container('auth');
      $sess->preloginroute = $routematch;
      $this->redirect($event, array('name' => 'login'));
		} else {
      if($routematch->getMatchedRouteName() == 'user' && strtolower($routematch->getParam('action')) == 'unauthorized') { return; }
      $auth->getStorage()->clear();
      $auth->getStorage()->write($user);
      if (!$auth->getIdentity()->authorized) {
        $this->redirect($event, array('name' => 'user'), array('controller' => 'user', 'action' => 'unauthorized'));
      }
    }
	}
}