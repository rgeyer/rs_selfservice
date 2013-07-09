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

namespace SelfService\Service\Entity;

use Doctrine\ODM\MongoDB\LockMode;
use SelfService\Document\Input;
use SelfService\Document\Server;
use SelfService\Document\Product;
use SelfService\Document\Instance;
use SelfService\Document\AlertSpec;
use SelfService\Document\Deployment;
use SelfService\Document\ServerArray;
use SelfService\Document\SecurityGroup;
use SelfService\Document\ServerTemplate;
use SelfService\Document\ElasticityParams;
use SelfService\Document\TextProductInput;
use SelfService\Document\SecurityGroupRule;
use SelfService\Document\CloudProductInput;
use SelfService\Document\SelectProductInput;
use SelfService\Document\CloudToResourceHref;
use SelfService\Document\DatacenterProductInput;
use SelfService\Document\ElasticityParamsBounds;
use SelfService\Document\ElasticityParamsPacing;
use SelfService\Document\ElasticityParamsSchedule;
use SelfService\Document\InstanceTypeProductInput;
use SelfService\Document\SecurityGroupRuleProtocolDetail;
use SelfService\Document\ElasticityParamsAlertSpecificParams;
use SelfService\Document\ElasticityParamsQueueSpecificParams;
use SelfService\Document\ElasticityParamsQueueSpecificParamsItemAge;
use SelfService\Document\ElasticityParamsQueueSpecificParamsQueueSize;

class ProductService extends BaseEntityService {

  /**
   * @return \SelfService\Service\RightScaleAPICache
   */
  protected function getRightscaleApiCache() {
    return $this->getServiceLocator()->get('RightScaleAPICache');
  }

  /**
   * @var string The name of the entity class for this service
   */
  protected $entityClass = 'SelfService\Document\Product';

  /**
   * @return \SelfService\Document\Product[] An array of all Product documents
   */
  public function findAll() {
    return parent::findAll();
  }

  /**
   * @param $id
   * @param $lockMode
   * @param null $lockVersion
   * @return \SelfService\Document\Product
   */
  public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
    return parent::find($id, $lockMode, $lockVersion);
  }

  public function update($id, array $params) {
    $dm = $this->getDocumentManager();
    $product = $this->find($id);
    foreach($params as $key => $value) {
      if(property_exists($product, $key)) {
        $product->{$key} = $value;
      }
    }
    $dm->persist($product);
    $dm->flush();
  }

  /**
   * Not implemented, please use createFromRideJson or createFromJson instead
   * @param array $params
   * @return void
   * @throws \BadMethodCallException always, please use createFromRideJson or createFromJson instead
   */
  public function create(array $params) {
    throw new \BadMethodCallException("Create is not implemented for the ProductService, please use createFromJson or createFromRideJson");
  }

  public function createFromJson($jsonStrOrObject) {
    # TODO: Do a validation against the schema?
    $jsonObj = $jsonStrOrObject;
    if(is_string($jsonStrOrObject)) {
      $jsonObj = json_decode($jsonStrOrObject);
    }
    $product = new Product();
    $product->resources = array();
    $dontSetProductVars = array('id','version','resources');
    foreach(get_object_vars($jsonObj) as $key => $val) {
      if(!in_array($key, $dontSetProductVars)) {
        $product->{$key} = $val;
      }
    }

    foreach($jsonObj->resources as $resource) {
      $this->stdClassToOdm($product, $resource);
    }

    $dm = $this->getDocumentManager();
    $dm->persist($product);
    $dm->flush();

    return $product;
  }

  /**
   * Takes the RIDE formatted JSON input and creates and persists a new ODM document for that product
   * @param $jsonstr The JSON payload from RIDE
   * @return \SelfService\Document\Product
   */
  public function createFromRideJson($jsonstr) {
    $dm = $this->getDocumentManager();
    $json = json_decode($jsonstr);

    $inputs = array();

    $cloud_href = array(
      "ref" => "cloud_product_input",
      "id" => "cloud_product_input"
    );

    $product = new Product();
    $product->launch_servers = false;
    $product->icon_filename = "zoidberg.png";
    $product->name = sprintf("RIDE-%s", time());
    $product->resources = array();

    $deployment = new Deployment();
    $deployment->id = "deployment";
    $deployment->name = $product->name;
    $deployment->inputs = array();
    $deployment->servers = array();
    $product->resources[] = $deployment;

    // Standard Inputs
    $cloud_meta = new CloudProductInput();
    $cloud_meta->id = "cloud_product_input";
    $cloud_meta->default_value = "/api/clouds/1";
    $cloud_meta->description = "The target cloud for servers";
    $cloud_meta->input_name = "cloud";
    $cloud_meta->display_name = "Cloud";
    $product->resources[] = $cloud_meta;

    $instance_meta = new InstanceTypeProductInput();
    $instance_meta->input_name = "instance_type";
    $instance_meta->display_name = "Instance Type";
    $instance_meta->description = "The instance type for all servers";
    $instance_meta->cloud_product_input = $cloud_href;
    $product->resources[] = $instance_meta;

    $secgrp = new SecurityGroup();
    $secgrp->id = "all_in_security_group";
    $secgrp->description = sprintf("Provisioned by rsss for %s", $product->name);
    $secgrp->cloud_href = $cloud_href;
    $secgrp->name = sprintf("%s-default", $product->name);
    $secgrp->security_group_rules = array();
    $product->resources[] = $secgrp;

    $tcprule = new SecurityGroupRule();
    $tcprule->id = "all_in_security_group_tcp_rule";
    $tcprule->source_type = "group";
    $protocol_details = new SecurityGroupRuleProtocolDetail();
    $protocol_details->start_port = 0;
    $protocol_details->end_port = 65535;
    $tcprule->protocol_details[] = $protocol_details;
    $tcprule->protocol = "tcp";
    $tcprule->ingress_group = array(
      "ref" => "security_group",
      "id" => "all_in_security_group"
    );
    $rulerel = array(
      "ref" => "security_group_rule",
      "id" => "all_in_security_group_tcp_rule",
      "nested" => true
    );
    $secgrp->security_group_rules[] = $rulerel;
    $product->resources[] = $tcprule;

    $udprule = new SecurityGroupRule();
    $udprule->id = "all_in_security_group_udp_rule";
    $udprule->source_type = "group";
    $protocol_details = new SecurityGroupRuleProtocolDetail();
    $protocol_details->start_port = 0;
    $protocol_details->end_port = 65535;
    $udprule->protocol_details[] = $protocol_details;
    $udprule->protocol = "udp";
    $udprule->ingress_group = array(
      "ref" => "security_group",
      "id" => "all_in_security_group"
    );
    $rulerel = array(
      "ref" => "security_group_rule",
      "id" => "all_in_security_group_tcp_rule",
      "nested" => true
    );
    $secgrp->security_group_rules[] = $rulerel;
    $product->resources[] = $udprule;

    foreach($json as $server_or_array) {
      switch(strtolower($server_or_array->type)) {
        case "deployment":
          $product->name = $server_or_array->nickname;
          $deployment->name = $product->name;
          break;
        case "server":
          $template = new ServerTemplate();
          $template->id = sprintf("%s_template", $server_or_array->info->nickname);
          $template->name = $server_or_array->st_name;
          $template->revision = $server_or_array->revision;
          $template->publication_id = $server_or_array->publication_id;
          $product->resources[] = $template;

          $server = new Server();
          $server->id = $server_or_array->info->nickname;
          $server->count = 1;
          $server_instance = new Instance();
          $server_instance->cloud_href = $cloud_href;
          $grouprel = array(
            "ref" => "security_group",
            "id" => "all_in_security_group"
          );
          $server_instance->security_groups[] = $grouprel;
          $templaterel = array(
            "ref" => "server_template",
            "id" => $template->id,
            "nested" => true
          );
          $server_instance->server_template[] = $templaterel;
          $server->instance[] = $server_instance;
          $server->name_prefix = $server_or_array->info->nickname;
          $product->resources[] = $server;
          $serverrel = array(
            "ref" => "server",
            "id" => $server->id,
            "nested" => true
          );
          $deployment->servers[] = $serverrel;

          foreach($server_or_array->inputs as $key=>$input) {
            $inputs[$key] = array(
              'value' => $input,
              'override' => (is_array($server_or_array->allowOverride) && in_array($key, $server_or_array->allowOverride)));
          }
          break;
        default:
          break;
      }
    }

    foreach($inputs as $key=>$value) {
      $metainput = new TextProductInput(); #  new InputProductMetaInput($key,$value['value']);
      $metainput->id = $key;
      $metainput->description = "Deployment level override for the input value ".$key;
      $metainput->display_name = $key;
      $metainput->input_name = $key;
      $metainput->default_value = $value['value'];
      $metainput->display = !$value['override'];
      $product->resources[] = $metainput;
      $inputrel = array(
        "ref" => "text_product_input",
        "id" => $metainput->id,
        "nested" => true
      );
      $deployment->inputs[] = $inputrel;
    }

    $dm->persist($product);
    $dm->flush();

    return $product;
  }

  protected function stdClassToOdm(&$odmProduct, $stdClass) {
    // Four cases
    // 4 - The property is a nested (schema) type with an embedded (odm)
    //  type.  The new ODM object must be instantiated and properties copied
    $odmObject = null;
    switch ($stdClass->resource_type) {
      case "deployment":
        $odmObject = new Deployment();
        break;
      case "input":
        $odmObject = new Input();
        break;
      case "instance":
        $odmObject = new Instance();
        break;
      case "security_group":
        $odmObject = new SecurityGroup();
        break;
      case "security_group_rule":
        $odmObject = new SecurityGroupRule();
        if(property_exists($stdClass, 'protocol_details') && $stdClass->protocol_details) {
          foreach($stdClass->protocol_details as $stdProtocolDetails) {
            $protocol_details = new SecurityGroupRuleProtocolDetail();
            $objectvars = get_object_vars($stdProtocolDetails);
            foreach($objectvars as $key => $val) {
              $this->doNeedfulToObjectVarOfStdClass($odmProduct, $protocol_details, $key, $val);
            }
            $odmObject->protocol_details[] = $protocol_details;
          }
        }
        break;
      case "server":
        $odmObject = new Server();
        break;
      case "server_array":
        $odmObject = new ServerArray();
        break;
      case "elasticity_params":
        $odmObject = new ElasticityParams();
        foreach($stdClass->bounds as $stdBounds) {
          $bounds = new ElasticityParamsBounds();
          $objectvars = get_object_vars($stdBounds);
          foreach($objectvars as $key => $val) {
            $this->doNeedfulToObjectVarOfStdClass($odmProduct, $bounds, $key, $val);
          }
          $odmObject->bounds[] = $bounds;
        }

        foreach($stdClass->pacing as $stdPacing) {
          $pacing = new ElasticityParamsPacing();
          $objectvars = get_object_vars($stdPacing);
          foreach($objectvars as $key => $val) {
            $this->doNeedfulToObjectVarOfStdClass($odmProduct, $pacing, $key, $val);
          }
          $odmObject->pacing[] = $pacing;
        }

        if(property_exists($stdClass, 'alert_specific_params') && $stdClass->alert_specific_params) {
          foreach($stdClass->alert_specific_params as $stdAlertSpecificParams) {
            $alert_specific_params = new ElasticityParamsAlertSpecificParams();
            $objectvars = get_object_vars($stdAlertSpecificParams);
            foreach($objectvars as $key => $val) {
              $this->doNeedfulToObjectVarOfStdClass($odmProduct, $alert_specific_params, $key, $val);
            }
            $odmObject->alert_specific_params[] = $alert_specific_params;
          }
        }

        if(property_exists($stdClass, 'queue_specific_params') && $stdClass->queue_specific_params) {
          foreach($stdClass->queue_specific_params as $stdQueueSpecificParams) {
            $queue_specific_params = new ElasticityParamsQueueSpecificParams();
            $objectvars = get_object_vars($stdQueueSpecificParams);
            foreach($objectvars as $key => $val) {
              $this->doNeedfulToObjectVarOfStdClass($odmProduct, $queue_specific_params, $key, $val);
            }
            if(property_exists($stdQueueSpecificParams, 'item_age') && $stdQueueSpecificParams->item_age) {
              foreach($stdQueueSpecificParams->item_age as $stdItemAge) {
                $item_age = new ElasticityParamsQueueSpecificParamsItemAge();
                $objectvars = get_object_vars($stdItemAge);
                foreach($objectvars as $key => $val) {
                  $this->doNeedfulToObjectVarOfStdClass($odmProduct, $item_age, $key, $val);
                }
                $queue_specific_params->item_age[] = $item_age;
              }
            }
            if(property_exists($stdQueueSpecificParams, 'queue_size') && $stdQueueSpecificParams->queue_size) {
              foreach($stdQueueSpecificParams->queue_size as $stdQueueSize) {
                $queue_size = new ElasticityParamsQueueSpecificParamsQueueSize();
                $objectvars = get_object_vars($stdQueueSize);
                foreach($objectvars as $key => $val) {
                  $this->doNeedfulToObjectVarOfStdClass($odmProduct, $queue_size, $key, $val);
                }
                $queue_specific_params->queue_size[] = $queue_size;
              }
            }
            $odmObject->queue_specific_params[] = $queue_specific_params;
          }
        }
        if(property_exists($stdClass, 'schedule') && is_array($stdClass->schedule)) {
          $odmObject->schedule = array();
          foreach($stdClass->schedule as $schedule) {
            $odmSchedule = new ElasticityParamsSchedule();
            $objectvars = get_object_vars($schedule);
            foreach($objectvars as $key => $val) {
              $this->doNeedfulToObjectVarOfStdClass($odmProduct, $odmSchedule, $key, $val);
            }
            $odmObject->schedule[] = $odmSchedule;
          }
        }
        break;
      case "server_template":
        $odmObject = new ServerTemplate();
        break;
      case "text_product_input":
        $odmObject = new TextProductInput();
        break;
      case "select_product_input":
        $odmObject = new SelectProductInput();
        break;
      case "cloud_product_input":
        $odmObject = new CloudProductInput();
        break;
      case "instance_type_product_input":
        $odmObject = new InstanceTypeProductInput();
        if(property_exists($stdClass, 'default_value') && is_array($stdClass->default_value)) {
          $odmObject->default_value = array();
          foreach($stdClass->default_value as $default_value) {
            $odmCloudToResourceHref = new CloudToResourceHref();
            $objectvars = get_object_vars($default_value);
            foreach($objectvars as $key => $val) {
              $this->doNeedfulToObjectVarOfStdClass($odmProduct, $odmCloudToResourceHref, $key, $val);
            }
            $odmObject->default_value[] = $odmCloudToResourceHref;
          }
        }
        break;
      case "datacenter_product_input":
        $odmObject = new DatacenterProductInput();
        if(property_exists($stdClass, 'default_value') && is_array($stdClass->default_value)) {
          $odmObject->default_value = array();
          foreach($stdClass->default_value as $default_value) {
            $odmCloudToResourceHref = new CloudToResourceHref();
            $objectvars = get_object_vars($default_value);
            foreach($objectvars as $key => $val) {
              $this->doNeedfulToObjectVarOfStdClass($odmProduct, $odmCloudToResourceHref, $key, $val);
            }
            $odmObject->default_value[] = $odmCloudToResourceHref;
          }
        }
        break;
      case "alert_spec":
        $odmObject = new AlertSpec();
        break;
    }
    $objectvars = get_object_vars($stdClass);
    foreach($objectvars as $key => $val) {
      $this->doNeedfulToObjectVarOfStdClass($odmProduct, $odmObject, $key, $val);
    }
    $odmProduct->resources[] = $odmObject;
  }

  protected function doNeedfulToObjectVarOfStdClass(&$odmProduct, &$odmObject, $key, $val, $key_is_array = false) {
    if(is_object($val)) {
      if(property_exists($val, "resource_type")) {
        // It's an embedded resource with an ODM type
        $this->stdClassToOdm($odmProduct, $val);
        $ref = array(
          "ref" => $val->resource_type,
          "id" => $val->id,
          "nested" => true
        );
        if($key_is_array) {
          $odmObject->{$key}[] = $ref;
        } else {
          $odmObject->{$key} = $ref;
        }
      } else if(property_exists($val, "ref") | property_exists($val, "cloud_product_input_id")) {
        // Casting to array here because although json_decode creates an stdClass
        // these references get stored in mongodb as hashes, as well as rehydrated
        // by mongo-odm as hashes.  Lots of unit tests were mistakenly passing
        // because they were fetching the cached object which represented these
        // as stdClass rather than a hash, which is what they'd be represented as
        // when fetched from mongodb.
        if($key_is_array) {
          $odmObject->{$key}[] = (array)$val;
        } else {
          $odmObject->{$key} = (array)$val;
        }
      }
    } else if (is_array($val)) {
      if(!property_exists($odmObject, $key)) {
        $odmObject->{$key} = array();
      }
      foreach($val as $subval) {
        $this->doNeedfulToObjectVarOfStdClass($odmProduct, $odmObject, $key, $subval, true);
      }
    } else {
      if($key_is_array) {
        $odmObject->{$key}[] = $val;
      } else {
        $odmObject->{$key} = $val;
      }
    }
  }

  /**
   * @param array $meta_input_ids An array of the unique ID's for metainputs
   * @param $object The ORM class to be converted to a stdClass
   * @param bool $meta_inputs_as_rels If true, properties which are of type
   * \SelfService\Entity\Provisionable\MetaInputs\ProductMetaInputBase will be
   * represented as a relationship link I.E. ["rel" => "meta_inputs", "id" => "1"].
   * If false, properties will be set to the value returned by \SelfService\Entity|Provisionable\MetaInputs\ProductMetaInputBase::getVal()
   * @return \stdClass
   */
  protected function ormToStdclass($meta_input_ids, $object, $meta_inputs_as_rels = true) {
    $stdClass = new \stdClass();
    foreach(get_object_vars($object) as $propname => $var) {
      if(is_a($var, 'SelfService\Entity\Provisionable\MetaInputs\ProductMetaInputBase')) {
        if($meta_inputs_as_rels && in_array($var->id, $meta_input_ids)) {
          $stdClass->{$propname} = array('rel' => 'meta_inputs', 'id' => $var->id);
        } else {
          $stdClass->{$propname} = $var->getVal();
        }
      } else if ($var !== null && $propname != 'id') {
        $stdClass->{$propname} = $var;
      }
    }
    return $stdClass;
  }

  public function odmToStdClass($odm) {
    $stdClass = new \stdClass();
    foreach(get_object_vars($odm) as $key => $val) {
      if(is_array($val)) {
        $stdClass->{$key} = array();
        foreach($val as $aryval) {
          if(array_key_exists('ref', $aryval)) {
            # By the time we get here, we should have resolved all the nested-ness and we need
            # to remove that decoration
            unset($aryval['nested']);
            $stdClass->{$key}[] = $aryval;
          } else {
            $stdClass->{$key}[] = is_scalar($aryval) ? $aryval : $this->odmToStdClass($aryval);
          }
        }
      } else if (get_class($val) == "Doctrine\ODM\MongoDB\PersistentCollection") {
        $stdClass->{$key} = array();
        foreach($val as $aryval) {
          $stdClass->{$key}[] = $this->odmToStdClass($aryval);
        }
      } else if (strpos(get_class($val), "SelfService\Document") === 0) {
        $stdClass->{$key} = $this->odmToStdClass($val);
      } else if ($val !== null) {
        # TODO: Does this ever get reached?
        $stdClass->{$key} = is_scalar($val) ? $val : $this->odmToStdClass($val);
      }
    }
    return $stdClass;
  }

  public function toInputJson($id) {
    $product = $this->find($id);
    $this->getDocumentManager()->detach($product);
    $product->replaceRefsWithConcreteResource(true);
    $stdClass = $this->odmToStdClass($product);
    return json_encode($stdClass);
  }

  public function toOutputJson($id, array $params = array()) {
    $product = $this->find($id);
    $this->detach($product);
    $product->resolveDepends($params);
    $product->mergeMetaInputs($params);
    $product->pruneBrokenRefs();
    $product->replaceRefsWithConcreteResource();
    $product->dedupeOnlyOneProperties();
    $stdClass = $this->odmToStdClass($product);
    return json_encode($stdClass);
  }

  /**
   * @param $id String The product ID for which to get inputs
   * @param array $params A hash of input values where the key is the id/name
   * of the field, and the value is the value.  The sort of key/value pairs you'd
   * expect from, say... An HTML Form..  Default is an empty array.
   * @return \SelfService\Document\AbstractProductInput[] The inputs which should be
   * displayed/processed given the current $params
   */
  public function inputs($id, array $params = array()) {
    $api_cache = $this->getRightscaleApiCache();
    $clouds = $api_cache->getClouds();
    $cloud_product_input_values = array();
    $cloud_capabilities_by_href = array();
    foreach($clouds as $cloud) {
      if(!in_array($cloud->href, array_keys($cloud_capabilities_by_href))) {
        $cloud_capabilities_by_href[$cloud->href] = array();
      }
      foreach($cloud->links as $link) {
        $cloud_capabilities_by_href[$cloud->href][] = $link->rel;
      }

      $cloud_product_input_values[] = array('name' => $cloud->name, 'href' => $cloud->href);
    }
    $inputs = array();
    $product = $this->find($id);
    $this->detach($product);
    $valuesByInputId = self::convertInputParamsToResourceIdKeys($product, $params);
    foreach($product->resources as $resource) {
      if($resource instanceof \SelfService\Document\AbstractProductInput) {
        # Begin opt out checks
        if(!self::hasDependsMatch($resource, $valuesByInputId)) { continue; }
        if($resource->required_cloud_capability !== null) {
          $match = $resource->required_cloud_capability['match'] ? : 'all';
          $value = $resource->required_cloud_capability['value'];
          $cloud_product_input_id = $resource->required_cloud_capability['cloud_product_input_id'];
          $cloud_capabilities = $cloud_capabilities_by_href[$valuesByInputId[$cloud_product_input_id]];
          switch ($match) {
            case "any":
              $intersect = array_intersect($cloud_capabilities, $value);
              if(count($intersect) <= 0) { continue 2; }
              break;
            case "all":
              $diff = array_diff($value, $cloud_capabilities);
              if(count($diff) != 0) { continue 2; }
              break;
            case "none":
              $intersect = array_intersect($cloud_capabilities, $value);
              if(count($intersect) != 0) { continue 2; }
              break;
            default: break;
          }
        }
        # End opt out checks

        # Begin value setting
        if ($resource instanceof \SelfService\Document\CloudProductInput) {
          $resource->values = $cloud_product_input_values;
        }

        if ($resource instanceof \SelfService\Document\InstanceTypeProductInput) {
          $resource->values = array();
          $cloud_href = $valuesByInputId[$resource->cloud_product_input['id']];
          $resource->cloud_href = $cloud_href;
          $client_id = \RGeyer\Guzzle\Rs\RightScaleClient::getIdFromRelativeHref($cloud_href);
          foreach($api_cache->getInstanceTypes($client_id) as $instance_type) {
            $resource->values[] = array('name' => $instance_type->name, 'href' => $instance_type->href);
          }
          $instance_types = $valuesByInputId[$resource->id];
          if($instance_types && strpos($instance_types, $cloud_href) === 0) {
            $resource->default_value = array((object)array('cloud_href' => $cloud_href, 'resource_hrefs' => $instance_types));
          }
        }

        if ($resource instanceof \SelfService\Document\DatacenterProductInput) {
          if(property_exists($resource, 'cloud_product_input')) {
            $cloud = $cloud_capabilities_by_href[$valuesByInputId[$resource->cloud_product_input['id']]];
            if(!in_array('datacenters', $cloud)) { continue; }
          }
          $resource->values = array();
          $cloud_href = $valuesByInputId[$resource->cloud_product_input['id']];
          $resource->cloud_href = $cloud_href;
          $client_id = \RGeyer\Guzzle\Rs\RightScaleClient::getIdFromRelativeHref($cloud_href);
          foreach($api_cache->getDatacenters($client_id) as $datacenter) {
            $resource->values[] = array('name' => $datacenter->name, 'href' => $datacenter->href);
          }
          $datacenters = $valuesByInputId[$resource->id];
          if(is_array($datacenters) && strpos($datacenters[0], $cloud_href) === 0) {
            $resource->default_value = array((object)array('cloud_href' => $cloud_href, 'resource_hrefs' => $datacenters));
          }
        }
        # End value setting

        $inputs[] = $resource;
      }
    }
    return $inputs;
  }

  /**
   * @param $product \SelfService\Document\Product An instance of a product to operate on
   * @param array $params An associative array of key/value pairs.  The key is
   * the input_name of the *_product_input, and the value is what will replace
   * all matching references.
   * @return array An associative array of key/value pairs.  The key is
   * the resource id of the *_product_input.  The value can be one of the following.
   *  * scalar - The value passed in as input, or the default value
   *  * array - Same as above, but for multi value types
   *  * stdClass - A class with the properties "cloud_product_input_id" and "value".
   *    This is used only for product_input types which depend upon the value of
   *    a cloud input.  The "cloud_product_input_id" will contain the resource id
   *    of the cloud input upon which this product_input depends.
   */
  public static function convertInputParamsToResourceIdKeys($product, $params) {
    # TODO: Replace this with an abstract base class so it can be determined
    # by "instanceof"
    $depends_on_cloud_array = array(
      "instance_type_product_input",
      "datacenter_product_input"
    );
    // Convert the params array from input_name:value to resource_id:value pairs
    $paramKeys = array_keys($params);
    $valuesByInputId = array();
    foreach($product->resources as $resource) {
      if($resource instanceof \SelfService\Document\AbstractProductInput) {
        if(in_array($resource->input_name, $paramKeys)) {
          $valuesByInputId[$resource->id] = $params[$resource->input_name];
        } else {
          $valuesByInputId[$resource->id] = $resource->default_value;
          // Need to include some more metadata for inputs which depend upon
          // the cloud input.
          if(in_array($resource->resource_type, $depends_on_cloud_array)) {
            $detailedValue = new \stdClass();
            $detailedValue->cloud_product_input_id = $resource->cloud_product_input["id"];
            $detailedValue->value = $valuesByInputId[$resource->id];
            $valuesByInputId[$resource->id] = $detailedValue;
          }
        }
      }
    }
    return $valuesByInputId;
  }

  /**
   * TODO: Refactoring so that product inputs do not have an "input_name" but just use
   * their unique ID instead. - https://github.com/rgeyer/rs_selfservice/issues/36
   *
   * @param $resource \SelfService\Document\AbstractProductInput|\SelfService\Document\AbstractResource
   * The resource to evaluate for a depends match
   * @param array $params A hash where the key is the id of the product input resource,
   * and the value is the current value of that product input.  This is in the form generated
   * by \SelfService\Service\Entity\ProductService::convertInputParamsToResourceIdKeys
   * @return bool
   */
  public static function hasDependsMatch(&$resource, array $params) {
    $does_match = true;
    $objvars = get_object_vars($resource);
    foreach($objvars as $key => $val) {
      if($key == "depends" && $val !== null) {
        $input_value_as_array = array_key_exists($resource->depends["id"], $params) ?
          (array)$params[$resource->depends["id"]] : array();
        $depends_value_as_array = (array)$resource->depends["value"];
        if($resource->depends["match"] == "any") {
          $intersect = array_intersect($depends_value_as_array, $input_value_as_array);
          $does_match = count($intersect) > 0;
        } else {
          $diff = array_diff($depends_value_as_array, $input_value_as_array);
          $does_match = count($diff) == 0;
        }
      } elseif (get_class($val) == "Doctrine\ODM\MongoDB\PersistentCollection") {
        foreach($val as $idx => $subresource) {
          if(!self::hasDependsMatch($subresource, $params)) {
            $resource->{$key}->unwrap()->removeElement($subresource);
          }
        }
      }
    }
    return $does_match;
    # TODO: Should I validate the depends['ref'] type to the input matched
    # by the depends['id']? I don't currently have the resource_type in $params
  }

}