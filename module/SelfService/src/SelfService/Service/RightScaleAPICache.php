<?php
/*
Copyright (c) 2012-2013 Ryan J. Geyer <me@ryangeyer.com>

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
use RGeyer\Guzzle\Rs\RightScaleClient;

class RightScaleAPICache {

  public function __construct(ServiceManager $sm) {
    $this->service_manager = $sm;
    $this->adapter = $sm->get('cache_storage_adapter');
    $this->adapter->getOptions()->setNamespace('rsapi');
    $this->adapter->getOptions()->setTtl(3600);
    $this->client = $sm->get('RightScaleAPIClient');
  }

  /**
   * @var ServiceManager
   */
  protected $service_manager;

  /**
   * @var CacheAdapterInterface
   */
  protected $adapter;

  /**
   * @var RightScaleClient
   */
  protected $client;

  public function getClouds() {
    $clouds = $this->adapter->getItem('clouds');
    if(!isset($clouds)) {
      $cloud = $this->client->newModel('Cloud');
      $clouds = $cloud->index();
      $cache_clouds = array();
      foreach($clouds as $idx => $cloud) {
        $cache_clouds[$idx] = $cloud->getParameters();
      }
      $this->adapter->setItem('clouds',$cache_clouds);
    } else {
      $retval = array();
      foreach($clouds as $idx => $cloud) {
        $clouds[$idx] = $this->client->newModel('Cloud', (object)$cloud);
      }
    }
    return $clouds;
  }

  public function getInstanceTypes($cloud_id) {
    $instance_types = $this->adapter->getItem('instance_types_'.$cloud_id);
    if(!isset($instance_types)) {
      $instance_type = $this->client->newModel('InstanceType');
      $instance_type->cloud_id = $cloud_id;
      $instance_types = $instance_type->index();
      $cache_instance_types = array();
      foreach($instance_types as $idx => $instance_type) {
        $cache_instance_types[$idx] = $instance_type->getParameters();
      }
      $this->adapter->setItem('instance_types_'.$cloud_id,$cache_instance_types);
    } else {
      foreach($instance_types as $idx => $instance_type) {
        $instance_types[$idx] = $this->client->newModel('InstanceType', (object)$instance_type);
      }
    }
    return $instance_types;
  }

}