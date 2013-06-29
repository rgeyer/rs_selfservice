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
      } else if(property_exists($val, "ref")) {
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

}