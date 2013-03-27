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

namespace SelfService\Service;

use Zend\ServiceManager\ServiceManager;

class CacheService {

  protected static $_singleton;

  protected $_serviceManager;

  protected function __construct(ServiceManager $sm) {
    $this->_serviceManager = $sm;
  }

  public static function get(ServiceManager $sm) {
    if(!CacheService::$_singleton) {
      CacheService::$_singleton = new CacheService($sm);
    } else if (CacheService::$_singleton->_serviceManager !== $sm) {
      CacheService::$_singleton->_serviceManager = $sm;
    }
    return CacheService::$_singleton;
  }

  public function clearNamespace($namespace) {
    $adapter = $this->_serviceManager->get('cache_storage_adapter');
    $adapter->getOptions()->setNamespace($namespace);
    $remove_list = array();
    foreach($adapter->getIterator() as $item) {
      $remove_list[] = $item;
    }
    $adapter->removeItems($remove_list);
  }

  public function updateClouds() {
    $api_cache = $this->_serviceManager->get('RightScaleAPICache');
    $api_cache->updateClouds();
  }

  public function updateInstanceTypes() {
    $api_cache = $this->_serviceManager->get('RightScaleAPICache');
    $clouds = $api_cache->getClouds();
    foreach($clouds as $cloud) {
      $api_cache->updateInstanceTypes($cloud->id);
    }
  }

  public function updateDatacenters() {
    $api_cache = $this->_serviceManager->get('RightScaleAPICache');
    $clouds = $api_cache->getClouds();
    foreach($clouds as $cloud) {
      if($cloud->supportsCloudFeature('datacenters')) {
        $api_cache->updateDatacenters($cloud->id);
      }
    }
  }

  public function updateServerTemplates() {
    $api_cache = $this->_serviceManager->get('RightScaleAPICache');
    $api_cache->updateServerTemplates();
  }

}