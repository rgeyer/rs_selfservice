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

namespace SelfService\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class Product {

  /**
   * Unique Identifier for the object in the database
   * @ODM\Id
   * @var string
   */
  public $id;

  /**
   * Semantic schema version
   * @ODM\String
   * @var string
   */
  public $version = "1.0.0";

  /**
   * Display name of the product in the RSSS UI
   * @ODM\String
   * @var string
   */
  public $name;

  /**
   * The filename of the icon found in public/images/icons
   * @ODM\String
   * @var string
   */
  public $icon_filename;

  /**
   * A boolean indicating if the servers should be launched immediately after
   * being created by the RSSS
   * @ODM\Boolean
   * @var bool
   */
  public $launch_servers;

  /**
   * @ODM\EmbedMany(
   *  discriminatorField="resource_type",
   *  discriminatorMap={
   *    "deployment"="Deployment",
   *    "input"="Input",
   *    "instance"="Instance",
   *    "security_group"="SecurityGroup",
   *    "security_group_rule"="SecurityGroupRule",
   *    "server"="Server",
   *    "alert_spec"="AlertSpec",
   *    "server_array"="ServerArray",
   *    "elasticity_params"="ElasticityParams",
   *    "server_template"="ServerTemplate",
   *    "text_product_input"="TextProductInput",
   *    "select_product_input"="SelectProductInput",
   *    "cloud_product_input"="CloudProductInput",
   *    "instance_type_product_input"="InstanceTypeProductInput",
   *    "datacenter_product_input"="DatacenterProductInput"
   *  }
   * )
   * @var (Deployment|Input|Instance|SecurityGroup|SecurityGroupRule|Server|AlertSpec|ServerArray|ElasticityParams|ServerTemplate|TextProductInput|SelectProductInput|CloudProductInput|InstanceTypeProductInput|DatacenterProductInput)[]
   */
  public $resources;

  protected $inputtypesarray = array(
    "text_product_input",
    "instance_type_product_input",
    "select_product_input",
    "datacenter_product_input",
    "cloud_product_input"
  );

  /**
   * @param array $params An associative array of key/value pairs.  The key is
   * the input_name of the *_product_input, and the value is what will replace
   * all matching references.
   * @return array An associative array of key/value pairs.  The key is
   * the resource id of the *_product_input, and the default value or override
   * value for that input.
   */
  protected function convertInputParamsToResourceIdKeys($params) {
    // Convert the params array from input_name:value to resource_id:value pairs
    $paramKeys = array_keys($params);
    $valuesByInputId = array();
    foreach($this->resources as $resource) {
      if(in_array($resource->resource_type, $this->inputtypesarray)) {
        if(in_array($resource->input_name, $paramKeys)) {
          $valuesByInputId[$resource->id] = $params[$resource->input_name];
        } else {
          $valuesByInputId[$resource->id] = $resource->default_value;
        }
      }
    }
    return $valuesByInputId;
  }

  public function pruneBrokenRefs() {
    $topLevelResourceIds = array();
    foreach($this->resources as $resource) {
      $topLevelResourceIds[] = $resource->id;
    }

    foreach($this->resources as $resource) {
      $this->isBrokenRef($resource, $topLevelResourceIds);
    }
  }

  protected function isBrokenRef(&$resource, $resource_ids) {
    $retval = false;
    if(property_exists($resource, "ref")) {
      return !in_array($resource->id, $resource_ids);
    } else {
      $objvars = get_object_vars($resource);
      foreach($objvars as $key => $val) {
        if(is_array($val)) {
          foreach($val as $idx => $subresource) {
            if($this->isBrokenRef($subresource, $resource_ids)) {
              unset($resource->{$key}[$idx]);
            }
          }
        }
        $resource->{$key} = array_merge($resource->{$key});
      }
    }
    return $retval;
  }

  /**
   * Removes all resources which have a "depends" property which does not match
   * the values passed in as $params
   * @param array $params An associative array of key/value pairs.  The key is
   * the input_name of the *_product_input, and the value is what will replace
   * all matching references.
   * @return void
   */
  public function resolveDepends(array $params) {
    $valuesByInputId = $this->convertInputParamsToResourceIdKeys($params);

    foreach($this->resources as $idx => $resource) {
      if(!$this->hasDependsMatch($resource, $valuesByInputId)) {
        unset($this->resources[$idx]);
      }
    }
  }

  protected function hasDependsMatch(&$resource, array $params) {
    $does_match = true;
    $objvars = get_object_vars($resource);
    foreach($objvars as $key => $val) {
      if($key == "depends" && $val !== null) {
        $input_value_as_array = array_key_exists($resource->depends->id, $params) ?
          (array)$params[$resource->depends->id] : array();
        $depends_value_as_array = (array)$resource->depends->value;
        if($resource->depends->match == "any") {
          $intersect = array_intersect($depends_value_as_array, $input_value_as_array);
          $does_match = count($intersect) > 0;
        } else {
          $diff = array_diff($depends_value_as_array, $input_value_as_array);
          $does_match = count($diff) == 0;
        }
      } elseif (get_class($val) == "Doctrine\ODM\MongoDB\PersistentCollection") {
        $resetAry = array();
        foreach($val as $idx => $subresource) {
          if($this->hasDependsMatch($subresource, $params)) {
            $resetAry[] = $subresource;
          }
        }
        $resource->{$key} = $resetAry;
      }
    }
    return $does_match;
    # TODO: Should I validate the depends->ref type to the input matched
    # by the depends->id? I don't currently have the resource_type in $params
  }

  /**
   * Replaces *_product_input references with the values passed in as $params
   * @param array $params An associative array of key/value pairs.  The key is
   * the input_name of the *_product_input, and the value is what will replace
   * all matching references.
   */
  public function mergeMetaInputs(array $params) {
    $valuesByInputId = $this->convertInputParamsToResourceIdKeys($params);

    foreach($this->resources as $resource) {
      $this->replaceInputRefsWithScalar($resource, $valuesByInputId);
    }
  }

  /**
   * @param $object An object who's properties will be iterated over.  Where a
   * property is a reference, it will be replaced by the value from the $params param
   * @param array $params An associative array of key/value pairs.  The key is the
   * id of the *_product_input ref, and the value will replace the reference.
   * @return void
   */
  protected function replaceInputRefsWithScalar(&$object, array $params) {
    $objvars = get_object_vars($object);
    foreach($objvars as $key => $val) {
      if($key == "depends") { continue; }
      if (property_exists($val, "ref") && preg_match('/^[a-z]+_product_input$/', $val->ref)) {
        $object->{$key} = $params[$val->id];
      } elseif (get_class($val) == "Doctrine\ODM\MongoDB\PersistentCollection") {
        foreach($val as $item) {
          $this->replaceInputRefsWithScalar($item, $params);
        }
      }
    }
  }
}