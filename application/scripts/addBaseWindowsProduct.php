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
$log = null;

if ($bootstrap->hasResource('Log')) {
	$log = $bootstrap->getResource('Log');
}

try {
	
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
$cloud_metainput->description = 'The target cloud for the 3-Tier';

$em->persist($cloud_metainput);
// END Cloud ProductMetaInput
	
// START default security group
$securityGroup = new SecurityGroup();
$securityGroup->name = new TextProductMetaInput('base');
$securityGroup->description = new TextProductMetaInput('Port 3389 and 80');
$securityGroup->cloud_id = $cloud_metainput;

$idx = 0;

$securityGroup->rules[$idx] = new SecurityGroupRule();
$securityGroup->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput('0.0.0.0/0');
$securityGroup->rules[$idx]->ingress_from_port = new NumberProductMetaInput(3389);
$securityGroup->rules[$idx]->ingress_to_port = new NumberProductMetaInput(3389);
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
$serverTemplate->version = new NumberProductMetaInput(54);
$serverTemplate->nickname = new TextProductMetaInput('Base ServerTemplate for Windows (v13.1)');
$serverTemplate->publication_id = new TextProductMetaInput('40720');

$server = new Server();
$server->cloud_id = $cloud_metainput;
$server->count = $count_metainput;
$server->instance_type = new TextProductMetaInput('m1.small');
$server->security_groups = array($securityGroup);
$server->server_template = $serverTemplate;
$server->nickname = new TextProductMetaInput('Base Windows ST');

$product = new Product();
$product->name = "Base Windows";
$product->icon_filename = "4f0e334ba64d1.png";
$product->security_groups = array($securityGroup);
$product->servers = array($server);
$product->meta_inputs = array($cloud_metainput, $count_metainput);
$product->launch_servers = true;

} catch (Exception $e) {
	if($log) { $log->err($e->getMessage()); }
	print_r($e);
}

try {
$em->persist($product);
$em->flush();
} catch (Exception $e) {
	if($log) { $log->err($e->getMessage()); }
	print $e->getMessage() . "\n";
	print $e->getTraceAsString();
}