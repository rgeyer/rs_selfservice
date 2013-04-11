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

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use SelfService\Entity\Provisionable\Server;
use SelfService\Entity\Provisionable\Product;
use SelfService\Entity\Provisionable\SecurityGroup;
use SelfService\Entity\Provisionable\ServerTemplate;
use SelfService\Entity\Provisionable\SecurityGroupRule;
use SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\CloudProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\InputProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\NumberProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\InstanceTypeProductMetaInput;

class ProductService extends BaseEntityService {

  /**
   * @var string The name of the entity class for this service
   */
  protected $entityClass = 'SelfService\Entity\Provisionable\Product';

  /**
   * @return \SelfService\Entity\Provisionable\Product[] An array of all Product entities
   */
  public function findAll() {
    return parent::findAll();
  }

  /**
   * @param $id
   * @param $lockMode
   * @param null $lockVersion
   * @return \SelfService\Entity\Provisionable\Product
   */
  public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
    return parent::find($id, $lockMode, $lockVersion);
  }

  public function update($id, array $params) {
    $em = $this->getEntityManager();
    $product = $this->find($id);
    foreach($params as $key => $value) {
      if(property_exists($product, $key)) {
        $product->{$key} = $value;
      }
    }
    $em->persist($product);
    $em->flush();
  }

  public function createFromRideJson($jsonstr) {
    $em = $this->getEntityManager();
    $json = json_decode($jsonstr);

    $inputs = array();

    $product = new Product();
    $product->servers = array();
    $product->meta_inputs = array();
    $product->launch_servers = false;
    $product->icon_filename = "zoidberg.png";
    $product->name = sprintf("RIDE-%s", time());

    // Standard Inputs
    $cloud_meta = new CloudProductMetaInput();
    $cloud_meta->default_value = 1;
    $cloud_meta->description = "The target cloud for servers";
    $cloud_meta->input_name = "cloud";
    $cloud_meta->display_name = "Cloud";
    $em->persist($cloud_meta);
    $product->meta_inputs[] = $cloud_meta;

    $instance_meta = new InstanceTypeProductMetaInput();
    $instance_meta->default_value = "default";
    $instance_meta->input_name = "instance_type";
    $instance_meta->display_name = "Instance Type";
    $instance_meta->description = "The instance type for all servers";
    $instance_meta->cloud = $cloud_meta;
    $em->persist($instance_meta);
    $product->meta_inputs[] = $instance_meta;

    $secgrp = new SecurityGroup();
    $secgrp->description = new TextProductMetaInput(sprintf("Provisioned by rsss for %s", $product->name));
    $secgrp->cloud_id = $cloud_meta;
    $secgrp->name = new TextProductMetaInput(sprintf("%s-default", $product->name));
    $em->persist($secgrp);
    $em->flush();

    $tcprule = new SecurityGroupRule();
    $tcprule->ingress_from_port = new NumberProductMetaInput(0);
    $tcprule->ingress_to_port = new NumberProductMetaInput(65535);
    $tcprule->ingress_protocol = new TextProductMetaInput("tcp");
    $tcprule->ingress_group = $secgrp;
    $secgrp->rules[] = $tcprule;
    $em->persist($secgrp);

    $udprule = new SecurityGroupRule();
    $udprule->ingress_from_port = new NumberProductMetaInput(0);
    $udprule->ingress_to_port = new NumberProductMetaInput(65535);
    $udprule->ingress_protocol = new TextProductMetaInput("udp");
    $udprule->ingress_group = $secgrp;
    $secgrp->rules[] = $udprule;
    $em->persist($secgrp);

    $product->security_groups[] = $secgrp;

    foreach($json as $server_or_array) {
      switch(strtolower($server_or_array->type)) {
        case "deployment":
          $product->name = $server_or_array->nickname;
          break;
        case "server":
          $template = new ServerTemplate();
          $template->nickname = new TextProductMetaInput($server_or_array->st_name);
          $template->version = new NumberProductMetaInput($server_or_array->revision);
          $template->publication_id = new TextProductMetaInput($server_or_array->publication_id);
          $em->persist($template);

          $server = new Server();
          $server->server_template = $template;
          $server->cloud_id = $cloud_meta;
          $server->count = new NumberProductMetaInput(1);
          $server->nickname = new TextProductMetaInput($server_or_array->info->nickname);
          $server->security_groups[] = $secgrp;
          $em->persist($server);

          $product->servers[] = $server;

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
      $metainput = new InputProductMetaInput($key,$value['value']);
      $metainput->description = "Deployment level override for the input value ".$key;
      $metainput->display_name = $key;
      $metainput->input_name = $key;
      $product->parameters[] = $metainput;
      if($value['override']) {
        $product->meta_inputs[] = $metainput;
      }
    }

    $em->persist($product);
    $em->flush();
  }

  public function remove($id) {
    $em = $this->getEntityManager();
    $product = $this->find($id);
    $metainputs = array();
    $security_groups = array();
    foreach($product->alerts as $alert) {
      $em->remove($alert);
    }
    foreach($product->parameters as $param) {
      $em->remove($param);
    }
    foreach($product->security_groups as $group) {
      foreach($group->rules as $rule) {
        $em->remove($rule);
      }
    }
    foreach($product->security_groups as $group) {
      $em->remove($group);
    }
    foreach($product->servers as $server) {
      $em->remove($server);
    }
    foreach($product->arrays as $array) {
      $em->remove($array);
    }
    foreach($product->meta_inputs as $input) {
      $em->remove($input);
    }
    # TODO: This leaves a lot of abandoned things which need to be cleaned up
    $em->remove($product);
    $em->flush();
    # delete from servers where id not in (select server_id from product_server);
  }

  /**
   * @param $meta_inputs
   * @param $object
   * @return
   */
  protected function ormToStdclass($meta_input_ids, $object) {
    $stdClass = new \stdClass();
    foreach(get_object_vars($object) as $propname => $var) {
      if(is_a($var, 'SelfService\Entity\Provisionable\MetaInputs\ProductMetaInputBase')) {
        if(in_array($var->id, $meta_input_ids)) {
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

  public function toJson($id, array $params = array()) {
    $config = $this->getServiceLocator()->get('Configuration');
    $owners = $config['rsss']['cloud_credentials']['owners'];
    $product = $this->find($id);
    $product->mergeMetaInputs($params);
    $jsonProduct = $this->ormToStdclass(array(), $product);
    $jsonProduct->server_templates = array();

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
          'extra' => $this->ormToStdclass(array(), $meta_input),
          'value' => $meta_input->getVal()
        );
    }
    $jsonProduct->security_groups = array();
    foreach($product->security_groups as $security_group) {
      $jsonSecurityGroup = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $security_group);
      $jsonSecurityGroup->rules = array();
      foreach($security_group->rules as $rule) {
        $jsonRule = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $rule);
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
      $jsonArray = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $array);
      $jsonProduct->server_templates[$array->server_template->id] = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $array->server_template);
      $jsonArray->server_template = array('rel' => 'server_templates', 'id' => $array->server_template->id);
      $jsonArray->security_groups = array();
      foreach($array->security_groups as $security_group) {
        $jsonArray->security_groups[] = array('rel' => 'security_groups', 'id' => $security_group->id);
      }
      $jsonProduct->arrays[$array->id] = $jsonArray;
    }
    $jsonProduct->servers = array();
    foreach($product->servers as $server) {
      $jsonServer = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $server);
      $jsonProduct->server_templates[$server->server_template->id] = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $server->server_template);
      $jsonServer->server_template = array('rel' => 'server_templates', 'id' => $server->server_template->id);
      $jsonServer->security_groups = array();
      foreach($server->security_groups as $security_group) {
        $jsonServer->security_groups[] = array('rel' => 'security_groups', 'id' => $security_group->id);
      }
      $jsonProduct->servers[$server->id] = $jsonServer;
    }
    $jsonProduct->alerts = array();
    foreach($product->alerts as $alert) {
      $jsonAlert = $this->ormToStdclass(array_keys($jsonProduct->meta_inputs), $alert);
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
        'extra' => $this->ormToStdclass(array(), $param)
      );
    }

    return json_encode($jsonProduct);
  }

}