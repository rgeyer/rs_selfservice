<?php
/*
Copyright (c) 2011 Ryan J. Geyer <me@ryangeyer.com>

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

require_once __DIR__ . '/bootstrapEnvironment.php';

$frontController = Zend_Controller_Front::getInstance();
$bootstrap = $application->getBootstrap()->bootstrap('doctrineEntityManager');
$em = $bootstrap->getResource('doctrineEntityManager');

$rule1 = new SecurityGroupRule();
$rule1->ingress_cidr_ips = "0.0.0.0/0";
$rule1->ingress_from_port = 22;
$rule1->ingress_to_port = 22;
$rule1->ingress_protocol = 'tcp';

$rule2 = new SecurityGroupRule();
$rule2->ingress_cidr_ips = "0.0.0.0/0";
$rule2->ingress_from_port = 80;
$rule2->ingress_to_port = 80;
$rule2->ingress_protocol = 'tcp';

$securityGroup = new SecurityGroup();
$securityGroup->name = "base";
$securityGroup->description = "Port 22 and 80";
$securityGroup->cloud_id = 1;
$securityGroup->rules[] = $rule1;
$securityGroup->rules[] = $rule2;

$serverTemplate = new ServerTemplate();
$serverTemplate->version = 41;
$serverTemplate->nickname = 'Base ServerTemplate for Linux (Chef)';

$server = new Server();
$server->cloud_id = 1;
$server->count = 1;
$server->instance_type = 'm1.small';
$server->security_groups[] = $securityGroup;
$server->server_template = $serverTemplate;
$server->nickname = "Base ST";

$product = new Product();
$product->name = "Base";
$product->icon_filename = "4f0e334ba64d1.png";
$product->security_groups[] = $securityGroup;
$product->servers[] = $server;

try {
$em->persist($product);
$em->flush();
} catch (Exception $e) {
	print $e->getMessage() . "\n";
}