<?php
// Inspired by http://stackoverflow.com/questions/8843092/zf2-get-controller-name-into-layout-views

namespace SelfService\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ControllerName extends AbstractHelper
{

  protected $routeMatch;

  public function __construct($routeMatch)
  {
    $this->routeMatch = $routeMatch;
  }

  public function __invoke()
  {
    if ($this->routeMatch) {
      $controller = $this->routeMatch->getParam('controller', 'index');
      return $controller;
    }
  }
}