<?php
namespace SelfService;
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use SelfService\Service\ProvisioningHelper;
use SelfService\Service\CleanupHelper;

return array(
  'router' => array(
    'routes' => array(
      'home' => array(
        'type' => 'Zend\Mvc\Router\Http\Literal',
        'options' => array(
          'route'    => '/',
          'defaults' => array(
            'controller' => 'SelfService\Controller\Index',
            'action'     => 'index',
          ),
        ),
      ),
      'admin' => array(
        'type'    => 'Literal',
        'options' => array(
          'route'    => '/admin',
          'defaults' => array(
            '__NAMESPACE__' => 'SelfService\Controller',
            'controller'    => 'Index',
            'action'        => 'adminindex',
          ),
        ),
      ),
      'admin/provisionedproducts' => array(
        'type' => 'Segment',
        'options' => array(
          'route' => '/admin/provisionedproducts[/:action][/:id]',
          'constraints' => array(
              'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
              'id' => '[0-9]+'
          ),
          'defaults' => array(
            '__NAMESPACE__' => 'SelfService\Controller',
            'controller'    => 'ProvisionedProduct',
            'action'        => 'index',
          )
        )
      ),
      'product' => array(
        'type' => 'Segment',
        'options' => array(
          'route' => '/product[/:action][/:id]',
          'constraints' => array(
              'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
              'id' => '[0-9]+'
          ),
          'defaults' => array(
            '__NAMESPACE__' => 'SelfService\Controller',
            'controller'    => 'Product',
          )
        )
      ),
      'productrendermetaform' => array(
        'type' => 'Segment',
        'options' => array(
          'route' => '/product/rendermetaform[/:id]',
          'constraints' => array(
              'id' => '[0-9]+'
          ),
          'defaults' => array(
            '__NAMESPACE__' => 'SelfService\Controller',
            'controller'    => 'Product',
            'action'        => 'rendermetaform',
          )
        )
      ),
      'login' => array(
        'type' => 'Segment',
        'options' => array(
          'route' => '/login[/:action]',
          'constraints' => array(
              'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
          ),
          'defaults' => array(
            '__NAMESPACE__' => 'SelfService\Controller',
            'controller'    => 'Login',
            'action'        => 'index',
          )
        )
      ),
      // The following is a route to simplify getting started creating
      // new controllers and actions without needing to create a new
      // module. Simply drop new controllers in, and you can access them
      // using the path /application/:controller/:action
      'self-service' => array(
        'type'    => 'Literal',
        'options' => array(
          'route'    => '/self-service',
          'defaults' => array(
            '__NAMESPACE__' => 'SelfService\Controller',
            'controller'    => 'Index',
            'action'        => 'index',
          ),
        ),
        'may_terminate' => true,
        'child_routes' => array(
          'default' => array(
            'type'    => 'Segment',
            'options' => array(
              'route'    => '/[:controller[/:action]]',
              'constraints' => array(
                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
              ),
              'defaults' => array(
              ),
            ),
          ),
        ),
      ),
    ),
  ),
  'console' => array(
    'router' => array(
      'routes' => array(
        'product' => array(
          'options' => array(
            'route' => 'product add [--name=<name>]',
            'defaults' => array(
              'controller' => 'SelfService\Controller\Product',
              'action' => 'consoleadd'
            )
          )
        ),
      )
    )
  ),
  'service_manager' => array(
    'factories' => array(
      'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory',
      'RightScaleAPIClient' => function($serviceManager) {
        $config = $serviceManager->get('Configuration');
        $rscreds = $config['rsss']['cloud_credentials']['rightscale'];
        # TODO: Client factory is redudant, eventually should be deprecated
        \RGeyer\Guzzle\Rs\Common\ClientFactory::setCredentials($rscreds['account_id'], $rscreds['email'], $rscreds['password']);
        return \RGeyer\Guzzle\Rs\RightScaleClient::factory(
          array(
            'acct_num' => $rscreds['account_id'],
            'email' => $rscreds['email'],
            'password' => $rscreds['password'],
            'version' => '1.5'
          )
        );
      },
      'rs_provisioning_helper' => function ($serviceManager) {
        $config = $serviceManager->get('Configuration');
        $rscreds = $config['rsss']['cloud_credentials']['rightscale'];
        $owners = $config['rsss']['cloud_credentials']['owners'];
        return new ProvisioningHelper(
          $rscreds['account_id'],
          $rscreds['email'],
          $rscreds['password'],
          $serviceManager->get('logger'),
          $owners
        );
      },
      'rs_cleanup_helper' => function ($serviceManager) {
        $config = $serviceManager->get('Configuration');
        $rscreds = $config['rsss']['cloud_credentials']['rightscale'];
        return new CleanupHelper(
          $rscreds['account_id'],
          $rscreds['email'],
          $rscreds['password'],
          $serviceManager->get('logger')
        );
      },
      'logger' => function() {
        $logger = new Logger();
        $writer = new Stream(__DIR__.'/../../../logs/application.log');
        # TODO: Will require the addition of a processor, implementing
        # Zend\Log\ProcessorInterface in order to add things like %request_id%

        #$formatter = new \Zend\Log\Formatter\Simple("%timestamp% %priorityName% (%request_id%): %message% %info%".PHP_EOL);
        #$writer->setFormatter($formatter);
        $logger->addWriter($writer);
        return $logger;
      },
    ),
  ),
  'translator' => array(
    'locale' => 'en_US',
    'translation_file_patterns' => array(
      array(
        'type'     => 'gettext',
        'base_dir' => __DIR__ . '/../language',
        'pattern'  => '%s.mo',
      ),
    ),
  ),
  'controllers' => array(
    'invokables' => array(
      'SelfService\Controller\Index' => 'SelfService\Controller\IndexController',
      'SelfService\Controller\Login' => 'SelfService\Controller\LoginController',
      'SelfService\Controller\ProvisionedProduct' => 'SelfService\Controller\ProvisionedProductController',
      'SelfService\Controller\Product' => 'SelfService\Controller\ProductController'
    ),
  ),
  'view_manager' => array(
    'display_not_found_reason' => true,
    'display_exceptions'       => true,
    'doctype'                  => 'HTML5',
    'not_found_template'       => 'error/404',
    'exception_template'       => 'error/index',
    'template_map' => array(
      'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
      'self-service/index/index' => __DIR__ . '/../view/self-service/index/index.phtml',
      'error/404'               => __DIR__ . '/../view/error/404.phtml',
      'error/index'             => __DIR__ . '/../view/error/index.phtml',
    ),
    'template_path_stack' => array(
      __DIR__ . '/../view',
    ),
    'strategies' => array(
      'ViewJsonStrategy'
    )
  ),
  'controller_plugins' => array(
    'invokables' => array(
      'GoogleAuthPlugin' => 'SelfService\Controller\Plugin\GoogleAuthPlugin'
    )
  ),
  // Doctrine config
  'doctrine' => array(
    'driver' => array(
      __NAMESPACE__ . '_driver' => array(
        'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
        'cache' => 'array',
        'paths' => array(__DIR__ . '/../src/' . __NAMESPACE__ . '/Entity')
      ),
      'orm_default' => array(
        'drivers' => array(
          __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
        )
      )
    )
  )
);
