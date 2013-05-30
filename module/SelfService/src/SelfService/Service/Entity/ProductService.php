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
use SelfService\Document\Server;
use SelfService\Document\Product;
use SelfService\Document\Instance;
use SelfService\Document\Deployment;
use SelfService\Document\SecurityGroup;
use SelfService\Document\ServerTemplate;
use SelfService\Document\SecurityGroupRule;
use SelfService\Document\SecurityGroupRuleProtocolDetail;
use SelfService\Document\TextProductInput;
use SelfService\Document\CloudProductInput;
use SelfService\Document\InstanceTypeProductInput;

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

  public function createFromRideJson($jsonstr) {
    $dm = $this->getDocumentManager();
    $json = json_decode($jsonstr);

    $inputs = array();

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
    $instance_meta->cloud_product_input = array(
      "ref" => "cloud_product_input",
      "id" => "cloud_product_input"
    );
    $product->resources[] = $instance_meta;

    $secgrp = new SecurityGroup();
    $secgrp->id = "all_in_security_group";
    $secgrp->description = sprintf("Provisioned by rsss for %s", $product->name);
    $secgrp->cloud_href = array(
      "ref" => "cloud_product_input",
      "id" => "cloud_product_input"
    );
    $secgrp->name = sprintf("%s-default", $product->name);
    $secgrp->security_group_rules = array();
    $product->resources[] = $secgrp;

    $tcprule = new SecurityGroupRule();
    $tcprule->id = "all_in_security_group_tcp_rule";
    $tcprule->source_type = "group";
    $tcprule->protocol_details = new SecurityGroupRuleProtocolDetail();
    $tcprule->protocol_details->start_port = 0;
    $tcprule->protocol_details->end_port = 65535;
    $tcprule->protocol = "tcp";
    $tcprule->ingress_group = array(
      "ref" => "security_group",
      "id" => "all_in_security_group"
    );
    $secgrp->security_group_rules[] = array(
      "ref" => "security_group_rule",
      "id" => "all_in_security_group_tcp_rule",
      "nested" => true
    );
    $product->resources[] = $tcprule;

    $udprule = new SecurityGroupRule();
    $udprule->id = "all_in_security_group_udp_rule";
    $udprule->source_type = "group";
    $udprule->protocol_details = new SecurityGroupRuleProtocolDetail();
    $udprule->protocol_details->start_port = 0;
    $udprule->protocol_details->end_port = 65535;
    $udprule->protocol = "udp";
    $udprule->ingress_group = array(
      "ref" => "security_group",
      "id" => "all_in_security_group"
    );
    $secgrp->security_group_rules[] = array(
      "ref" => "security_group_rule",
      "id" => "all_in_security_group_udp_rule",
      "nested" => true
    );
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
          $server_instance->cloud_href = array(
            "ref" => "cloud_product_input",
            "id" => "cloud_product_input"
          );
          $server_instance->security_groups = array(
            "ref" => "security_group",
            "id" => "all_in_security_group"
          );
          $server_instance->server_template = array(
            "ref" => "server_template",
            "id" => $template->id,
            "nested" => true
          );
          $server->instance = $server_instance;
          $server->name_prefix = $server_or_array->info->nickname;
          $product->resources[] = $server;
          $deployment->servers[] = array(
            "ref" => "server",
            "id" => $server->id,
            "nested" => true
          );

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
      $deployment->inputs[] = array(
        "ref" => "text_product_input",
        "id" => $metainput->id,
        "nested" => true
      );
    }

    $dm->persist($product);
    $dm->flush();
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

  public function toJson($id, array $params = array(), $include_meta_inputs = true) {
    $config = $this->getServiceLocator()->get('Configuration');
    $owners = $config['rsss']['cloud_credentials']['owners'];
    $product = $this->find($id);
    $product->mergeMetaInputs($params);
    $jsonProduct = $this->ormToStdclass(array(), $product, $include_meta_inputs);
    if(!$include_meta_inputs) {
      unset($jsonProduct->meta_inputs);
    }
    $jsonProduct->server_templates = array();

    if($include_meta_inputs) {
      $jsonProduct->meta_inputs = array();
      foreach($product->meta_inputs as $meta_input) {
        $type = '';
        switch(strtolower(get_class($meta_input))) {
          case 'selfservice\entity\provisionable\metainputs\cloudproductmetainput':
            $type = 'cloud';
            break;
          case 'selfservice\entity\provisionable\metainputs\textproductmetainput':
            $type = 'text';
            break;
          case 'selfservice\entity\provisionable\metainputs\inputproductmetainput':
            $type = 'input';
            break;
          case 'selfservice\entity\provisionable\metainputs\instancetypeproductmetainput':
            $type = 'instancetype';
            break;
          default:
            $type = 'unknown';
            break;
        }
        $jsonProduct->meta_inputs[$meta_input->id] =
          array(
            'type' => $type,
            'extra' => $this->ormToStdclass(array(), $meta_input, $include_meta_inputs),
            'value' => $meta_input->getVal()
          );
      }
    }
    $jsonProduct->security_groups = array();
    foreach($product->security_groups as $security_group) {
      $jsonSecurityGroup = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $security_group, $include_meta_inputs);
      $jsonSecurityGroup->rules = array();
      foreach($security_group->rules as $rule) {
        $jsonRule = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $rule, $include_meta_inputs);
        if($rule->ingress_group) {
          $jsonRule->ingress_group = array('rel' => 'security_groups', 'id' => $rule->ingress_group->id);
          $cloud = $jsonSecurityGroup->cloud_id;
          if(is_array($cloud)) {
            $jsonRule->ingress_owner = $owners[$jsonProduct->meta_inputs[strval($cloud['id'])]['value']];
          } else {
            $jsonRule->ingress_owner = $owners[$cloud];
          }
        }
        $jsonSecurityGroup->rules[] = $jsonRule;
      }
      $jsonProduct->security_groups[$security_group->id] = $jsonSecurityGroup;
    }
    $jsonProduct->arrays = array();
    foreach($product->arrays as $array) {
      $jsonArray = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $array, $include_meta_inputs);
      $jsonProduct->server_templates[$array->server_template->id] = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $array->server_template, $include_meta_inputs);
      $jsonArray->server_template = array('rel' => 'server_templates', 'id' => $array->server_template->id);
      $jsonArray->security_groups = array();
      foreach($array->security_groups as $security_group) {
        $jsonArray->security_groups[] = array('rel' => 'security_groups', 'id' => $security_group->id);
      }
      $jsonProduct->arrays[$array->id] = $jsonArray;
    }
    $jsonProduct->servers = array();
    foreach($product->servers as $server) {
      $jsonServer = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $server, $include_meta_inputs);
      $jsonProduct->server_templates[$server->server_template->id] = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $server->server_template, $include_meta_inputs);
      $jsonServer->server_template = array('rel' => 'server_templates', 'id' => $server->server_template->id);
      $jsonServer->security_groups = array();
      foreach($server->security_groups as $security_group) {
        $jsonServer->security_groups[] = array('rel' => 'security_groups', 'id' => $security_group->id);
      }
      $jsonProduct->servers[$server->id] = $jsonServer;
    }
    $jsonProduct->alerts = array();
    foreach($product->alerts as $alert) {
      $jsonAlert = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $alert, $include_meta_inputs);
      $jsonAlert->subjects = array();
      foreach($alert->subjects as $subject) {
        $rel = array('rel' => 'unknown');
        switch(strtolower(get_class($subject))) {
          case 'selfservice\entity\provisionable\server':
            $rel = array('rel' => 'servers', 'id' => $subject->id);
            break;
          case 'selfservice\entity\provisionable\serverarray':
            $rel = array('rel' => 'arrays', 'id' => $subject->id);
            break;
        }
        $jsonAlert->subjects[] = $rel;
      }
      $jsonProduct->alerts[$alert->id] = $jsonAlert;
    }
    $jsonProduct->parameters = array();
    foreach($product->parameters as $param) {
      $jsonProduct->parameters[] = array(
        'name' => $param->rs_input_name,
        'value' => $param->getVal(),
        'extra' => $this->ormToStdclass(array(), $param, $include_meta_inputs)
      );
    }

    return json_encode($jsonProduct);
  }

}