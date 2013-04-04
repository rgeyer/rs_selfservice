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
    # TODO: This leaves a lot of abandoned things which need to be cleaned up
    parent::remove($id);
  }

}