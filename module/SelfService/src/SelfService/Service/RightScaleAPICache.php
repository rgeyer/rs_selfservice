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
    $config = $this->service_manager->get('Configuration');
    $acct_id = $config['rsss']['cloud_credentials']['rightscale']['account_id'];
    $this->adapter = $sm->get('cache_storage_adapter');
    $this->adapter->getOptions()->setNamespace('rsapi'.$acct_id);
    $this->adapter->getOptions()->setTtl(3600);
    $this->client = $sm->get('RightScaleAPIClient');
  }

  /**
   * @var ServiceManager
   */
  protected $service_manager;

  /**
   * @var \Zend\Cache\Storage\Adapter\AbstractAdapter
   */
  protected $adapter;

  /**
   * @var RightScaleClient
   */
  protected $client;

  public function getClouds() {
    $clouds = $this->adapter->getItem('clouds');
    if(!isset($clouds)) {
      $clouds = $this->updateClouds();
    } else {
      foreach($clouds as $idx => $cloud) {
        $clouds[$idx] = $this->client->newModel('Cloud', (object)$cloud);
      }
    }
    return $clouds;
  }

  public function removeClouds() {
    $this->adapter->removeItem('clouds');
  }

  /**
   * @param bool $assumeExists A boolean indicating if the clouds key already exists in the cache.  This prevents (re)checking when this method is called to set cache the value for the first time, such as from getClouds()
   */
  public function updateClouds() {
    $cloud = $this->client->newModel('Cloud');
    $clouds = $cloud->index();
    $cache_clouds = array();
    foreach($clouds as $idx => $cloud) {
      $cache_clouds[$idx] = $cloud->getParameters();
    }
    if ($this->adapter->hasItem('clouds')) {
      $this->adapter->replaceItem('clouds',$cache_clouds);
    } else {
      $this->adapter->setItem('clouds',$cache_clouds);
    }
    return $clouds;
  }

  public function getInstanceTypes($cloud_id) {
    $item_key = "instance_types_".intval($cloud_id);
    $instance_types = $this->adapter->getItem($item_key);
    if(!isset($instance_types)) {
      $instance_types = $this->updateInstanceTypes($cloud_id);
    } else {
      foreach($instance_types as $idx => $instance_type) {
        $instance_types[$idx] = $this->client->newModel('InstanceType', (object)$instance_type);
      }
    }
    return $instance_types;
  }

  public function removeInstanceTypes($cloud_id) {
    $item_key = "instance_types_".intval($cloud_id);
    $this->adapter->removeItem($item_key);
  }

  public function updateInstanceTypes($cloud_id) {
    $item_key = "instance_types_".intval($cloud_id);
    $instance_type = $this->client->newModel('InstanceType');
    $instance_type->cloud_id = $cloud_id;
    $instance_types = $instance_type->index();
    $cache_instance_types = array();
    foreach($instance_types as $idx => $instance_type) {
      $cache_instance_types[$idx] = $instance_type->getParameters();
    }
    if ($this->adapter->hasItem($item_key)) {
      $this->adapter->replaceItem($item_key,$cache_instance_types);
    } else {
      $this->adapter->setItem($item_key,$cache_instance_types);
    }
    return $instance_types;
  }

  public function getDatacenters($cloud_id) {
    $item_key = 'datacenters_'.intval($cloud_id);
    $dcs = $this->adapter->getItem($item_key);
    if(!isset($dcs)) {
      $dcs = $this->updateDatacenters($cloud_id);
    } else {
      foreach($dcs as $idx => $dc) {
        $dcs[$idx] = $this->client->newModel('DataCenter', (object)$dc);
      }
    }
    return $dcs;
  }

  public function removeDatacenters($cloud_id) {
    $item_key = 'datacenters_'.intval($cloud_id);
    $this->adapter->removeItem($item_key);
  }

  public function updateDatacenters($cloud_id) {
    $item_key = 'datacenters_'.intval($cloud_id);
    $dc = $this->client->newModel('DataCenter');
    $dc->cloud_id = strval($cloud_id);
    $dcs = $dc->index();
    $cache_dcs = array();
    foreach($dcs as $idx => $dc) {
      $cache_dcs[$idx] = $dc->getParameters();
    }
    if ($this->adapter->hasItem($item_key)) {
      $this->adapter->replaceItem($item_key,$cache_dcs);
    } else {
      $this->adapter->setItem($item_key,$cache_dcs);
    }
    return $dcs;
  }

  public function getServerTemplates() {
    $sts = $this->adapter->getItem('server_templates');
    if(!isset($sts)) {
      $sts = $this->updateServerTemplates();
    } else {
      foreach($sts as $idx => $st) {
        $sts[$idx] = $this->client->newModel('ServerTemplate', (object)$st);
      }
    }
    return $sts;
  }

  public function removeServerTemplates() {
    $this->adapter->removeItem('server_templates');
  }

  /**
   * @param bool $assumeExists A boolean indicating if the server_templates key already exists in the cache.  This prevents (re)checking when this method is called to set cache the value for the first time, such as from getServerTemplates()
   */
  public function updateServerTemplates() {
    $st = $this->client->newModel('ServerTemplate');
    $sts = $st->index();
    $cache_sts = array();
    foreach($sts as $idx => $st) {
      $cache_sts[$idx] = $st->getParameters();
    }
    if ($this->adapter->hasItem('server_templates')) {
      $this->adapter->replaceItem('server_templates',$cache_sts);
    } else {
      $this->adapter->setItem('server_templates',$cache_sts);
    }
    return $sts;
  }

}