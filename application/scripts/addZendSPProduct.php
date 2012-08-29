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

// START Deployment Name MetaInput
$deployment_name = new TextProductMetaInput();
$deployment_name->default_value = 'Zend SP';
$deployment_name->input_name = 'deployment_name';
$deployment_name->display_name = 'Deployment Name';
$deployment_name->description = 'The name of the deployment which will be created in RightScale';

$em->persist($deployment_name);
// END Deployment Name MetaInput

// Declare all of the security groups first, so that the rules can reference other groups before they're
// persisted in the DB
$zend_app_sg = new SecurityGroup();
$zend_cm_sg = new SecurityGroup();
$zend_mysql_sg = new SecurityGroup();
$zend_haproxy_sg = new SecurityGroup();
	
// START zend-default Security Group
$zend_default_sg = new SecurityGroup();
$zend_default_sg->name = new TextProductMetaInput("zend-default");
$zend_default_sg->description = new TextProductMetaInput("zend solution packs");
$zend_default_sg->cloud_id = $cloud_metainput;

$idx = 0;

$zend_default_sg->rules[$idx] = new SecurityGroupRule();
$zend_default_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_default_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(22);
$zend_default_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(22);
$zend_default_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

$em->persist($zend_default_sg);
// END zend-default Security Group

// START zend-app Security Group
$zend_app_sg->name = new TextProductMetaInput("zend-app");
$zend_app_sg->description = new TextProductMetaInput("zend solution packs");
$zend_app_sg->cloud_id = $cloud_metainput;

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
$zend_app_sg->rules[$idx]->ingress_group = $zend_haproxy_sg;
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(8000);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(8000);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_group = $zend_cm_sg;
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10085);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10085);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_group = $zend_cm_sg;
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10085);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10085);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('udp');
$idx++;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10081);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10081);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_app_sg->rules[$idx] = new SecurityGroupRule();
$zend_app_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10082);
$zend_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10082);
$zend_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$em->persist($zend_app_sg);
// END zend-app Security Group

// START zend-cm Security Group
$zend_cm_sg->name = new TextProductMetaInput("zend-cm");
$zend_cm_sg->description = new TextProductMetaInput("zend solution packs");
$zend_cm_sg->cloud_id = $cloud_metainput;

$idx = 0;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_group = $zend_app_sg;
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(3306);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(3306);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_group = $zend_app_sg;
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10085);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10085);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_group = $zend_app_sg;
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10085);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10085);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('udp');
$idx++;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_group = $zend_cm_sg;
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(1);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(65535);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_group = $zend_cm_sg;
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(1);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(65535);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('udp');
$idx++;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_group = $zend_cm_sg;
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(-1);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(-1);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('icmp');
$idx++;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10081);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10081);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$zend_cm_sg->rules[$idx] = new SecurityGroupRule();
$zend_cm_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_cm_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(10082);
$zend_cm_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(10082);
$zend_cm_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$em->persist($zend_cm_sg);
// END zend-cm Security Group

// START zend-mysql Security Group
$zend_mysql_sg->name = new TextProductMetaInput("zend-mysql");
$zend_mysql_sg->description = new TextProductMetaInput("zend solution packs");
$zend_mysql_sg->cloud_id = $cloud_metainput;

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
// END zend-mysql Security Group

// START zend-haproxy Security Group
$zend_haproxy_sg->name = new TextProductMetaInput("zend-haproxy");
$zend_haproxy_sg->description = new TextProductMetaInput("zend solution packs");
$zend_haproxy_sg->cloud_id = $cloud_metainput;

$idx = 0;

$zend_haproxy_sg->rules[$idx] = new SecurityGroupRule();
$zend_haproxy_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
$zend_haproxy_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(80);
$zend_haproxy_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(80);
$zend_haproxy_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
$idx++;

$em->persist($zend_haproxy_sg);
// END zend-haproxy Security Group

// START zend_server 1
$zend_server_st = new ServerTemplate();
$zend_server_st->version = new NumberProductMetaInput(7);
$zend_server_st->nickname = new TextProductMetaInput('ZEND PHP5 Zend Server - Zend Solution Pack - 11H1');

$zend_server = new Server();
$zend_server->cloud_id = $cloud_metainput;
$zend_server->count = new NumberProductMetaInput(1);
$zend_server->instance_type = new TextProductMetaInput('m1.large');
$zend_server->security_groups = array($zend_default_sg, $zend_app_sg);
$zend_server->server_template = $zend_server_st;
$zend_server->nickname = new TextProductMetaInput("App1");

$em->persist($zend_server);
// END zend_server 1

// START zend_cm server
$zend_cm_st = new ServerTemplate();
$zend_cm_st->version = new NumberProductMetaInput(5);
$zend_cm_st->nickname = new TextProductMetaInput('ZEND Zend Server Cluster Manager local DB - Zend Solution Pack');

$zend_cm = new Server();
$zend_cm->cloud_id = $cloud_metainput;
$zend_cm->count = new NumberProductMetaInput(1);
$zend_cm->instance_type = new TextProductMetaInput("m1.small");
$zend_cm->security_groups = array($zend_default_sg, $zend_cm_sg);
$zend_cm->server_template = $zend_cm_st;
$zend_cm->nickname = new TextProductMetaInput("CM1");

$em->persist($zend_cm);
// END zend_cm server

// START zend_db server(s)
$zend_db_st = new ServerTemplate();
$zend_db_st->version = new NumberProductMetaInput(16);
$zend_db_st->nickname = new TextProductMetaInput('Database Manager with MySQL 5.1 - 11H1');

$zend_db = new Server();
$zend_db->cloud_id = $cloud_metainput;
$zend_db->count = new NumberProductMetaInput(2);
$zend_db->instance_type = new TextProductMetaInput("m1.small");
$zend_db->security_groups = array($zend_default_sg, $zend_mysql_sg);
$zend_db->server_template = $zend_db_st;
// This is really just a prefix, it'll get an index numeral appended to it.
$zend_db->nickname = new TextProductMetaInput("DB");

$em->persist($zend_db);
// END zend_db server(s)

// START zend_lb server(s)
$zend_lb_st = new ServerTemplate();
$zend_lb_st->version = new NumberProductMetaInput(9);
$zend_lb_st->nickname = new TextProductMetaInput('RightScale Load Balancer with Apache/HAProxy - 11H1');

$zend_lb = new Server();
$zend_lb->cloud_id = $cloud_metainput;
$zend_lb->count = new NumberProductMetaInput(2);
$zend_lb->instance_type = new TextProductMetaInput("m1.small");
$zend_lb->security_groups = array($zend_default_sg, $zend_haproxy_sg);
$zend_lb->server_template = $zend_lb_st;
// This is really just a prefix, it'll get an index numeral appended to it.
$zend_lb->nickname = new TextProductMetaInput("LB");

$em->persist($zend_lb);
// END zend_lb server(s)

// START App Server Array
$zend_server_ary = new ServerArray();
$zend_server_ary->cloud_id = $cloud_metainput;
$zend_server_ary->min_count = new NumberProductMetaInput(1);
$zend_server_ary->max_count = new NumberProductMetaInput(10);
$zend_server_ary->type = new TextProductMetaInput("alert");
$zend_server_ary->tag = new TextProductMetaInput("zendarray");
$zend_server_ary->instance_type = new TextProductMetaInput('m1.large');
$zend_server_ary->security_groups = array($zend_default_sg, $zend_app_sg);
$zend_server_ary->server_template = $zend_server_st;
$zend_server_ary->nickname = new TextProductMetaInput("ZendArray");

$em->persist($zend_server_ary);
// END App Server Array

$product = new Product();
$product->name = "Zend SP";
$product->icon_filename = "4f0e334ba64d1.png";
$product->security_groups = array($zend_app_sg, $zend_cm_sg, $zend_default_sg, $zend_haproxy_sg, $zend_mysql_sg);
$product->servers = array($zend_server, $zend_cm, $zend_db, $zend_lb);
$product->arrays = array($zend_server_ary);
$product->meta_inputs = array($cloud_metainput, $deployment_name);
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