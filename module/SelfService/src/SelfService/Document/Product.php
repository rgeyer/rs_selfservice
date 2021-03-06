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
    if(is_array($resource) && array_key_exists("ref", $resource)) {
      return !in_array($resource["id"], $resource_ids);
    } else {
      $objvars = get_object_vars($resource);
      foreach($objvars as $key => $val) {
        if(is_array($val)) {
          foreach($val as $idx => $subresource) {
            if($this->isBrokenRef($subresource, $resource_ids)) {
              unset($resource->{$key}[$idx]);
            }
          }
          $resource->{$key} = array_merge($resource->{$key});
        }
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
    $valuesByInputId = \SelfService\Service\Entity\ProductService::convertInputParamsToResourceIdKeys($this, $params);

    foreach($this->resources as $idx => $resource) {
      if($resource instanceof \SelfService\Document\AbstractProductInput) { continue; }
      if(!\SelfService\Service\Entity\ProductService::hasDependsMatch($resource, $valuesByInputId)) {
        $this->resources->unwrap()->removeElement($resource);
      }
    }
  }

  /**
   * Replaces *_product_input references with the values passed in as $params
   * @param array $params An associative array of key/value pairs.  The key is
   * the input_name of the *_product_input, and the value is what will replace
   * all matching references.
   */
  public function mergeMetaInputs(array $params) {
    $valuesByInputId = \SelfService\Service\Entity\ProductService::convertInputParamsToResourceIdKeys($this, $params);

    foreach($this->resources as $resource) {
      if(strpos($resource->resource_type, "product_input") > 0) {
        $this->resources->unwrap()->removeElement($resource);
      }
      $this->replaceInputRefsWithScalar($resource, $valuesByInputId);
    }
  }

  /**
   * @param \stdClass $object An object who's properties will be iterated over.  Where a
   * property is a reference, it will be replaced by the value from the $params param
   * @param array $params An associative array of key/value pairs.  The key is the
   * id of the *_product_input ref, and the value will replace the reference.
   * @return void
   */
  protected function replaceInputRefsWithScalar(&$object, array $params) {
    $objvars = get_object_vars($object);
    foreach($objvars as $key => $val) {
      if ($key == "depends") { continue; }
      if (is_array($val) && array_key_exists("ref", $val) && preg_match('/^[a-z_]+_product_input$/', $val["ref"])) {
        $derived_val = $params[$val["id"]];
        if($derived_val instanceof \stdClass && property_exists($derived_val, "cloud_product_input_id")) {
          $compare_cloud_href = $params[$derived_val->cloud_product_input_id];
          # Initialize the reference to null
          $object->{$key} = null;
          foreach($derived_val->value as $cloud_to_resource_href_hash) {
            if($cloud_to_resource_href_hash->cloud_href == $compare_cloud_href) {
              $resource_href = $cloud_to_resource_href_hash->resource_hrefs;
              $object->{$key} = $val["ref"] == "instance_type_product_input" ? array_pop($resource_href) : $resource_href;
            }
          }
        } else {
          $object->{$key} = $params[$val["id"]];
        }
      } elseif (get_class($val) == "Doctrine\ODM\MongoDB\PersistentCollection") {
        foreach($val as $item) {
          $this->replaceInputRefsWithScalar($item, $params);
        }
      }
    }
  }

  public function replaceRefsWithConcreteResource($nested_only = false) {
    $resources_by_id = array();
    $resources_to_delete = array();
    foreach($this->resources as $resource) {
      if(strpos($resource->resource_type, "product_input") === false) {
        $resources_by_id[$resource->id] = $resource;
      }
    }

    foreach($this->resources as $resource) {
      $this->_replaceRefsWithConcreteResource($resource, $resources_by_id, $resources_to_delete, $nested_only);
    }

    foreach($resources_to_delete as $resource_to_delete) {
      $this->resources->unwrap()->removeElement($resource_to_delete);
    }
  }

  protected $_protectedResourceTypes = array(
    "security_group"
  );

  protected function _replaceRefsWithConcreteResource(&$object, $resources_by_id, &$resources_to_delete, $nested_only) {
    if(is_array($object) && array_key_exists("ref", $object) && strpos($object["ref"], "product_input") === false) {
      if(!in_array($object["ref"], $this->_protectedResourceTypes)
        && (($object["nested"] & $nested_only) | !$nested_only)) {
        $resources_to_delete[$object["id"]] = $resources_by_id[$object["id"]];
        return $resources_by_id[$object["id"]];
      } else {
        return $object;
      }
    }
    $objvars = get_object_vars($object);
    foreach($objvars as $key => $val) {
      if (is_array($val) && !array_key_exists("ref", $val)) {
        $ary = array();
        foreach($val as $item) {
          $ary[] = $this->_replaceRefsWithConcreteResource($item, $resources_by_id, $resources_to_delete, $nested_only);
        }
        $object->{$key} = $ary;
      }
    }
    # TODO: This seems to work, but has a bad code smell..
    return $object;
  }

  public function dedupeOnlyOneProperties() {
    # TODO: This dependency seems odd..
    $this->replaceRefsWithConcreteResource();

    foreach($this->resources as $resource) {
      $this->_dedupeOnlyOneProperties($resource);
    }
  }

  protected function _dedupeOnlyOneProperties(&$resource) {
    $objvars = get_object_vars($resource);
    foreach($objvars as $key => $val) {
      $reflection_class = new \ReflectionClass(get_class($resource));
      $reflection_property = $reflection_class->getProperty($key);
      if(is_array($val)) {
        if(strpos($reflection_property->getDocComment(), "@OnlyOne") !== FALSE) {
          $resource->{$key} = array_shift($val);
          $this->_dedupeOnlyOneProperties($resource->{$key});
        } else {
          foreach($val as $subval) {
            $this->_dedupeOnlyOneProperties($subval);
          }
        }
      } elseif (get_class($val) == "Doctrine\ODM\MongoDB\PersistentCollection") {
        if(strpos($reflection_property->getDocComment(), "@OnlyOne") !== FALSE) {
          $resource->{$key} = $val->first();
          $this->_dedupeOnlyOneProperties($resource->{$key});
        } else {
          foreach($val as $subval) {
            $this->_dedupeOnlyOneProperties($subval);
          }
        }
      } else {
        $this->_dedupeOnlyOneProperties($resource->{$key});
      }
    }
  }
}