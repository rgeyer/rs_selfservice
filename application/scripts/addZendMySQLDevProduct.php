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

if ($bootstrap->hasResource('Log')) {
	$log = $bootstrap->getResource('Log');
}

try {
	
// START Cloud ProductMetaInput
$cloud_metainput = new CloudProductMetaInput();
$cloud_metainput->default_value = 1;
$cloud_metainput->input_name = 'cloud';
$cloud_metainput->display_name = 'Cloud';
$cloud_metainput->description = 'The AWS cloud to create the Zend Solution Pack in';

$em->persist($cloud_metainput);
// END Cloud ProductMetaInput

// Declare all of the security groups first, so that the rules can reference other groups before they're
// persisted in the DB
$zend_app_sg = new SecurityGroup();
$zend_mysql_sg = new SecurityGroup();
	
// START zend-default Security Group
$zend_default_sg = new SecurityGroup();
$zend_default_sg->name = new TextProductMetaInput("zend-default");
$zend_default_sg->description = new TextProductMetaInput("zend solution packs");
$zend_default_sg->cloud_id = $cloud_metainput;

$em->persist($zend_default_sg);
// END zend-default Security Group

// START zend-app Security Group
$zend_app_sg->name = new TextProductMetaInput("zend-app");
$zend_app_sg->description = new TextProductMetaInput("zend solution packs");
$zend_app_sg->cloud_id = $cloud_metainput;

$em->persist($zend_app_sg);
// END zend-app Security Group

// START zend-mysql Security Group
$zend_mysql_sg->name = new TextProductMetaInput("zend-mysql");
$zend_mysql_sg->description = new TextProductMetaInput("zend solution packs");
$zend_mysql_sg->cloud_id = $cloud_metainput;

$em->persist($zend_mysql_sg);
// END zend-mysql Security Group

// START Security Group Rules
$idx = 0;

$zend_default_sg->rules[$idx] = new SecurityGroupRule();
$zend_default_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_default_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(22);
$zend_default_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(22);
$zend_default_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

$em->persist($zend_default_sg);

$idx = 0;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_group = $zend_app_sg;
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(1);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(65535);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_group = $zend_app_sg;
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(1);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(65535);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('udp');
$idx++;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_group = $zend_app_sg;
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(-1);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(-1);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('icmp');
$idx++;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10081);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10082);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$em->persist($zend_app_sg);

$idx = 0;

$zend_mysql_sg->rules[$idx] = new SecurityGroupRule();
$zend_mysql_sg->rules[$idx]->ingress_group = $zend_app_sg;
$zend_mysql_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(3306);
$zend_mysql_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(3306);
$zend_mysql_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_mysql_sg->rules[$idx] = new SecurityGroupRule();
$zend_mysql_sg->rules[$idx]->ingress_group = $zend_mysql_sg;
$zend_mysql_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(1);
$zend_mysql_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(65535);
$zend_mysql_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$em->persist($zend_mysql_sg);

// END Security Group Rules

// START zend_server
$zend_server_st = new ServerTemplate();
$zend_server_st->version = new NumberProductMetaInput(1);
$zend_server_st->nickname = new TextProductMetaInput('Zend Server 5.6 NoDeploy');
$zend_server_st->publication_id = new TextProductMetaInput('0');

$zend_server = new Server();
$zend_server->cloud_id = $cloud_metainput;
$zend_server->count = new NumberProductMetaInput(1);
$zend_server->instance_type = new TextProductMetaInput('m1.large');
$zend_server->security_groups = array($zend_default_sg, $zend_app_sg);
$zend_server->server_template = $zend_server_st;
$zend_server->nickname = new TextProductMetaInput("Zend Server");

$em->persist($zend_server);
// END zend_server

// START zend_db server
# TODO: Update to v12.11 or something, the publication ID is blank/invalid so the template must already be imported
$zend_db_st = new ServerTemplate();
$zend_db_st->version = new NumberProductMetaInput(116);
$zend_db_st->nickname = new TextProductMetaInput('Database Manager for MySQL 5.1');
$zend_db_st->publication_id = new TextProductMetaInput('0');

$zend_db = new Server();
$zend_db->cloud_id = $cloud_metainput;
$zend_db->count = new NumberProductMetaInput(1);
$zend_db->instance_type = new TextProductMetaInput("m1.small");
$zend_db->security_groups = array($zend_default_sg, $zend_mysql_sg);
$zend_db->server_template = $zend_db_st;
// This is really just a prefix, it'll get an index numeral appended to it.
$zend_db->nickname = new TextProductMetaInput("DB");

$em->persist($zend_db);
// END zend_db server

$product = new Product();
$product->name = "Zend MySQL DevEnv";
$product->icon_filename = "zend.png";
$product->security_groups = array($zend_app_sg, $zend_default_sg, $zend_mysql_sg);
$product->servers = array($zend_server, $zend_db);
$product->meta_inputs = array($cloud_metainput);
$product->launch_servers = false;

} catch (Exception $e) {
	if($log) {
		$log->err($e->getMessage());
	}
	print_r($e);
}

try {
	$em->persist($product);
	$em->flush();
} catch (Exception $e) {
	if($log) { $log->err($e->getMessage()); }
	print $e->getMessage() . "\n";
}