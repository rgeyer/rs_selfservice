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

namespace SelfService\Product;

use Doctrine\ORM\EntityManager;
use SelfService\Entity\Provisionable\Server;
use SelfService\Entity\Provisionable\Product;
use SelfService\Entity\Provisionable\AlertSpec;
use SelfService\Entity\Provisionable\ServerArray;
use SelfService\Entity\Provisionable\SecurityGroup;
use SelfService\Entity\Provisionable\ServerTemplate;
use SelfService\Entity\Provisionable\SecurityGroupRule;
use SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\CloudProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\NumberProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\InstanceTypeProductMetaInput;

class baselinux {

  public static function add(EntityManager $em) {
    $product = new Product();

    // START Count MetaInput
    $count_metainput = new NumberProductMetaInput();
    $count_metainput->default_value = 1;
    $count_metainput->input_name = 'instance_count';
    $count_metainput->display_name = 'Count';
    $count_metainput->description = 'The number of instances to create and launch';

    $em->persist($count_metainput);
    // END Count MetaInput

    // START Cloud ProductMetaInput
    $cloud_metainput = new CloudProductMetaInput();
    $cloud_metainput->default_value = 1;
    $cloud_metainput->input_name = 'cloud';
    $cloud_metainput->display_name = 'Cloud';
    $cloud_metainput->description = 'The target cloud for the servers';

    $em->persist($cloud_metainput);
    // END Cloud ProductMetaInput

    // START InstanceType ProductMetaInput
    $instance_metainput = new InstanceTypeProductMetaInput();
    $instance_metainput->cloud = $cloud_metainput;
    $instance_metainput->default_value = 'default';
    $instance_metainput->input_name = 'instance_type';
    $instance_metainput->display_name = 'Instance Type';
    $instance_metainput->description = 'The instance type for the servers';

    $em->persist($instance_metainput);
    // END InstanceType ProductMetaInput

    // START default security group
    $securityGroup = new SecurityGroup();
    $securityGroup->name = new TextProductMetaInput('base');
    $securityGroup->description = new TextProductMetaInput('Port 22 and 80');
    $securityGroup->cloud_id = $cloud_metainput;

    $idx = 0;

    $securityGroup->rules[$idx] = new SecurityGroupRule();
    $securityGroup->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
    $securityGroup->rules[$idx]->ingress_from_port = new NumberProductMetaInput(22);
    $securityGroup->rules[$idx]->ingress_to_port = new NumberProductMetaInput(22);
    $securityGroup->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

    $idx++;

    $securityGroup->rules[$idx] = new SecurityGroupRule();
    $securityGroup->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
    $securityGroup->rules[$idx]->ingress_from_port = new NumberProductMetaInput(80);
    $securityGroup->rules[$idx]->ingress_to_port = new NumberProductMetaInput(80);
    $securityGroup->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

    $em->persist($securityGroup);
    // START default security group

    $serverTemplate = new ServerTemplate();
    $serverTemplate->version = new NumberProductMetaInput(121);
    $serverTemplate->nickname = new TextProductMetaInput('Base ServerTemplate for Linux (v13.2.1)');
    $serverTemplate->publication_id = new TextProductMetaInput('46542');

    $server = new Server();
    $server->cloud_id = $cloud_metainput;
    $server->count = $count_metainput;
    $server->instance_type = $instance_metainput;
    $server->security_groups = array($securityGroup);
    $server->server_template = $serverTemplate;
    $server->nickname = new TextProductMetaInput('Base ST');

    $product->name = "Base";
    $product->icon_filename = "redhat.png";
    $product->security_groups = array($securityGroup);
    $product->servers = array($server);
    $product->meta_inputs = array($cloud_metainput, $instance_metainput, $count_metainput);
    $product->launch_servers = true;

    $em->persist($product);
    $em->flush();
  }

}