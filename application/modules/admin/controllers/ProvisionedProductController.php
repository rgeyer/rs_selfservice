<?php
/*
 Copyright (c) 2012 Ryan J. Geyer <me@ryangeyer.com>

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

use Guzzle\Rs\Common\ClientFactory;
use Guzzle\Aws\Ec2\Command\DeleteKeyPair;
use Guzzle\Aws\Ec2\Command\DescribeKeyPairs;
use Guzzle\Aws\Ec2\Command\DeleteSecurityGroup;
use Guzzle\Aws\Ec2\Command\RevokeSecurityGroupIngress;
use Guzzle\Aws\Ec2\Command\DescribeSecurityGroups;
use Guzzle\Rs\Model\ServerArray;
use Guzzle\Rs\Model\Server;
use Guzzle\Rs\Model\SshKey;
use Guzzle\Rs\Model\Deployment;
use Guzzle\Rs\Model\SecurityGroup;
use Guzzle\Aws\Ec2\Ec2Client;

/**
 * ProvisionedproductController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com> 
 */
class Admin_ProvisionedproductController extends \SelfService\controller\BaseController {
	
	private function meta_up_product($product, $request) {
		$this->meta_up_object($product, $request);
		foreach($product->security_groups as $security_group) {
			$this->meta_up_object($security_group, $request);
		}
		
		foreach($product->servers as $server) {
			$this->meta_up_object($server, $request);
		}
		
		foreach($product->arrays as $array) {
			$this->meta_up_object($array, $request);
		}
		
		foreach($product->alerts as $alert) {
			$this->meta_up_object($alert, $request);
		}
	}
	
	private function meta_up_object($object, $request) {
		foreach(get_object_vars($object) as $var) {
			if(is_a($var, 'ProductMetaInputBase') && $var->input_name && $request->has($var->input_name)) {
				$var->setVal($request->getParam($var->input_name));				
			}
		}
	}
	
	private function meta_down_product($product, $request, &$response) {
		$this->meta_down_object($product, $request, $response);
		foreach($product->security_groups as $security_group) {			
			$this->meta_down_object($security_group, $request, $response);
		}
		
		foreach($product->servers as $server) {
			$this->meta_down_object($server, $request, $response);
		}
		
		foreach($product->arrays as $array) {
			$this->meta_down_object($array, $request, $response);
		}
		
		foreach($product->alerts as $alert) {
			$this->meta_down_object($alert, $request, $response);
		}
	}
	
	private function meta_down_object($object, $request, &$response) {
		if(!$response[get_class($object)]) {
			$response[get_class($object)] = array();			
		}
		$current_element = array();
		foreach(get_object_vars($object) as $name => $val) {
			
			if(is_a($val, 'ProductMetaInputBase')) {
				$current_element[$name] = $val->getVal();
			} else {
				$current_element[$name] = $val;
			}
		}
		$response[get_class($object)][] = $current_element;
	}
	
	public function indexAction() {
		$dql = 'SELECT pp FROM ProvisionedProduct pp';
		$query = $this->em->createQuery($dql);
		$result = $query->getResult();
		
		$this->view->assign('provisioned_products', $result);	
		
		$actions = array(
				'del' => array(
						'uri_prefix' => $this->_helper->url('cleanup', 'provisionedproduct', 'admin'),
						'img_path' => '/images/delete.png'
				)
		);
		$this->view->assign('actions', $actions);
	}
	
	public function provisionAction() {
		$now = time();
		$response = array ('result' => 'success' );
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$creds = $bootstrap->getResource('cloudCredentials');
		
		if ($this->_request->has ( 'id' )) {						
			ClientFactory::setCredentials( $creds->rs_acct, $creds->rs_email, $creds->rs_pass );
			$api = ClientFactory::getClient();
			
			$product_id = $this->_request->getParam ( 'id' );
			$dql = "SELECT p FROM Product p WHERE p.id = " . $product_id;
			$result = $this->em->createQuery ( $dql )->getResult ();
			
			if (count ( $result ) == 1) {				
				$st = new \Guzzle\Rs\Model\ServerTemplate ();
				$api_server_templates = $st->index ();
				$api_security_groups = array();
				$api_servers = array();
				
				$sshkeys = array();
				
				$product = $result[0];
				
				$this->meta_up_product($product, $this->_request);
				
				$prov_prod = new ProvisionedProduct();
				$prov_prod->createdate = new DateTime();
				$prov_prod->owner = $this->em->getRepository('User')->find(Zend_Auth::getInstance()->getIdentity()->id);
				$prov_prod->product = $product;
				
				try {
					// TODO: For now assume we're creating a new SSH key and reusing
					// it, but this should really be configurable
					for($i = 1; $i <= 7; $i++) {
						$keyname = sprintf ( "rsss-%s-%s", $product->name, $now );
						$sshkeys[$i] = new \Guzzle\Rs\Model\SshKey ();
						$sshkeys[$i]->aws_key_name = $keyname;
						$sshkeys[$i]->cloud_id = $i;				
						$sshkeys[$i]->create();
						$prov_key = new ProvisionedSshKey();
						$prov_key->href = $sshkeys[$i]->href;
						$prov_key->cloud_id = $i;
						$prov_prod->provisioned_objects[] = $prov_key;				
						
						$this->log->info(sprintf("Created SSH Key - Cloud: %s Name: %s ID: %s", $i, $keyname, $sshkeys[$i]->id));
					}
					
					$deplname = sprintf ( "rsss-%s-%s", $product->name, $now );
					$deployment = new \Guzzle\Rs\Model\Deployment ();
					$deployment->nickname = $deplname;
					$deployment->description = sprintf ( "Created by rs_selfservice for the '%s' product", $product->name );
					$deployment->create();
					$prov_depl = new ProvisionedDeployment();
					$prov_depl->href = $deployment->href;
					$prov_prod->provisioned_objects[] = $prov_depl;
					
					$response['url'] = str_replace('/api', '', $deployment->href);
					
					$this->log->info(sprintf("Created Deployment - Name: %s ID: %s", $deplname, $deployment->id));
					
					$this->log->info("About to provision " . count($product->security_groups) . " Security Groups");
					// Create the Security Groups
					foreach ( $product->security_groups as $security_group ) {
						$this->log->info("The Security Group is a " . get_class($security_group));
						$this->log->info("The Security Group description is a " . get_class($security_group->description));
						$secGrpBaseName = $security_group->name->getVal();
						$secGrpPrefixedName = sprintf ( "rsss-%s-%s", $secGrpBaseName, $now );
						
						$this->log->info("About to provision with description of " . $security_group->description->getVal());
						
						$secGrp = new SecurityGroup();
						$secGrp->aws_group_name = $secGrpPrefixedName;
						$secGrp->aws_description = $security_group->description->getVal();
						$secGrp->cloud_id = $security_group->cloud_id->getVal();
						$secGrp->create();
						$prov_grp = new ProvisionedSecurityGroup();
						$prov_grp->href = $secGrp->href;
						$prov_grp->cloud_id = $security_group->cloud_id->getVal();
						$prov_prod->provisioned_objects[] = $prov_grp;					
						$this->log->info(sprintf("Created Security Group - Name: %s ID: %s", $secGrpPrefixedName, $secGrp->id));
						// This feels a bit hacky?
						$secGrp->find_by_id ( $secGrp->id );
						$api_security_groups[$security_group->id] = array ('api' => $secGrp, 'model' => $security_group );
					}
					
					// Add the rules to the security groups
					foreach ( $api_security_groups as $security_group ) {						
						$secGrpBaseName = $security_group['model']->name->getVal();
						$secGrpPrefixedName = sprintf ( "rsss-%s-%s", $secGrpBaseName, $now );
						
						$this->log->info("About to provision " . count($security_group['model']->rules) . " rules for Security Group " . $secGrpPrefixedName);
						foreach ( $security_group['model']->rules as $rule ) {
							$params = array(
								'id' => $security_group['api']->id,
								'ec2_security_group[protocol]' => $rule->ingress_protocol->getVal(),
								'ec2_security_group[from_port]' => $rule->ingress_from_port->getVal(),
								'ec2_security_group[to_port]' => $rule->ingress_to_port->getVal()
							);
							if ($rule->ingress_group) {
								$params = array_merge($params,
									array(
										'ec2_security_group[owner]' => $security_group['api']->aws_owner,
										'ec2_security_group[group]' => sprintf ( "rsss-%s-%s", $rule->ingress_group->getVal(), $now)
									)
								);
							} else {
								$params = array_merge($params,
									array('ec2_security_group[cidr_ips]' => $rule->ingress_cidr_ips->getVal())
								);
							}
							
							$command = $api->getCommand( 'ec2_security_groups_update', $params );
							$command->execute();
							$result = $command->getResult();
							$this->log->info(
								sprintf("Created Security Group Rule - Group Name: %s Rule: protocol: %s ports: %s..%s %s: %s",
									$secGrpPrefixedName,
									$rule->ingress_protocol->getVal(),
									$rule->ingress_from_port->getVal(),
									$rule->ingress_to_port->getVal(),
									$rule->ingress_group ? 'group' : 'IPs',
									$rule->ingress_group ? $rule->ingress_group->getVal() : $rule->ingress_cidr_ips->getVal(),
									$secGrp->id)
							);
						}
					}
					
					$this->log->info("About to provision " . count($product->servers) . " different types of servers");
					
					foreach ( $product->servers as $server ) {
						$messages = '';
						$st = null;
						foreach( $api_server_templates as $api_st ) {
							$messages .= $api_st->nickname . " " . $api_st->updated_at->format('Y-m-d H:i:s') . ' ' . $api_st->version . '<br/>';
							if ($api_st->nickname == $server->server_template->nickname->getVal() && $api_st->version == $server->server_template->version->getVal()) {
								$st = $api_st->href;
							}
						}
						
						if (!$st) {
							$response ['result'] = 'error';
							$response ['error'] = 'A server template with nickname "' . $server->server_template->nickname->getVal() . '" and version "' . $server->server_template->version->getVal() . '" was not found!';
							$this->log->err($response['error']);
							break;
						}
						
						$server_secgrps = array();
						foreach ( $server->security_groups as $secgrp ) {
							if (array_key_exists( $secgrp->id, $api_security_groups )) {
								$server_secgrps[] = $api_security_groups[$secgrp->id]['api']->href;
							}
						}
						
						$this->log->info("About to provision " . $server->count->getVal() . " servers of type " . $server->nickname->getVal());
						
						for ($i=1; $i <= $server->count->getVal(); $i++) {
							$api_server = new \Guzzle\Rs\Model\Server ();
							$api_server->nickname = $server->nickname->getVal();
							if($server->count->getVal() > 1) {
								$api_server->nickname .= $i;
							}
							$api_server->ec2_ssh_key_href = $sshkeys[$server->cloud_id->getVal()]->href;
							$api_server->ec2_security_groups_href = $server_secgrps;
							$api_server->server_template_href = $st;
							$api_server->deployment_href = $deployment->href;
							$api_server->cloud_id = $server->cloud_id->getVal();
							$api_server->instance_type = $server->instance_type->getVal();
							$api_server->create ();
							$prov_serv = new ProvisionedServer();
							$prov_serv->href = $api_server->href;
							$prov_serv->cloud_id = $server->cloud_id->getVal();
							$prov_prod->provisioned_objects[] = $prov_serv;						
							$this->log->info(sprintf("Created Server - Name: %s ID: %s", $server->nickname->getVal(), $api_server->id), $server);
							$debug_api_server = array ('api' => $api_server, 'model' => $server );
							$api_servers[$server->id] = $debug_api_server;							
						}
					}
					
					foreach($product->arrays as $array) {
						$st = null;
						foreach ( $api_server_templates as $api_st ) {
							$messages .= $api_st->nickname . " " . $api_st->updated_at->format ( 'Y-m-d H:i:s' ) . ' ' . $api_st->version . '<br/>';
							if ($api_st->nickname == $array->server_template->nickname->getVal() && $api_st->version == $array->server_template->version->getVal()) {
								$st = $api_st->href;
							}
						}
						
						$array_secgrps = array ();
						foreach ( $array->security_groups as $secgrp ) {
							if (array_key_exists ( $secgrp->id, $api_security_groups )) {
								$array_secgrps[] = $api_security_groups[$secgrp->id]['api']->href;
							}
						}
						
						$params = array(
							'cloud_id' => $array->cloud_id->getVal(),
							'server_array[nickname]' => $array->nickname->getVal(),
							'server_array[deployment_href]' => $deployment->href,
							'server_array[array_type]' => $array->type->getVal(),
							'server_array[ec2_security_groups_href]' => $array_secgrps,
							'server_array[server_template_href]' => $st,
							'server_array[ec2_ssh_key_href]' => $sshkeys[$array->cloud_id->getVal()]->href,
							'server_array[voters_tag]' => $array->tag->getVal(),
							'server_array[elasticity][min_count]' => $array->min_count->getVal(),
							'server_array[elasticity][max_count]' => $array->max_count->getVal(),
							'server_array[instance_type]' => $array->instance_type->getVal()
						);
						
						$command = $api->getCommand( 'server_arrays_create', $params );
						$command->execute();
						$result = $command->getResult();
						
						$prov_ary = new ProvisionedArray();
						$prov_ary->href = $command->getResponse()->getHeader('Location');
						$prov_prod->provisioned_objects[] = $prov_ary;
						$this->log->info(sprintf("Created Array - Name: %s ID: %s", $array->nickname->getVal(), "N/A"), $array);
					}
					
					foreach($product->alerts as $alert) {
						$alert_spec_subjects = array();
						foreach($alert->subjects as $alert_spec_subject) {
							if(array_key_exists($alert_spec_subject->id, $api_servers)) {
								$alert_spec_subjects[] = $api_servers[$alert_spec_subject->id]['api']->href;
							}
						}
						
						$params = array(
							'alert_spec[name]' => $alert->name->getVal(),
							'alert_spec[file]' => $alert->file->getVal(),
							'alert_spec[variable]' => $alert->variable->getVal(),
							'alert_spec[condition]' => $alert->cond->getVal(),
							'alert_spec[threshold]' => $alert->threshold->getVal(),
							'alert_spec[duration]' => $alert->duration->getVal(),
							'alert_spec[subject_type]' => 'Server',
							'alert_spec[action]' => $alert->action->getVal()
						);
						
						if($alert->action->getVal() == 'vote') {
							$params['alert_spec[vote_tag]'] = $alert->vote_tag->getVal();
							$params['alert_spec[vote_type]'] = $alert->vote_type->getVal();
						} else if ($alert->action->getVal() == 'escalate') {
							$params['alert_spec[escalation_name]'] = $alert->escalation_name->getVal();
						}
						
						foreach($alert_spec_subjects as $subject_href) {
							if(!$subject_href) { continue; }
							$params['alert_spec[subject_href]'] = $subject_href;							
							$command = $api->getCommand('alert_specs_create', $params);
							$command->execute();
							$result = $command->getResult();
													
							$this->log->info(sprintf("Created Alert Spec - Name: %s For Subject: %s", $alert->name->getVal(), $subject_href), $alert);
						}
					}
				} catch (Exception $e) {
					$response['result'] = 'error';
					$response['error'] = $e->getMessage();
					$this->log->err("An error occurred provisioning the product. Error: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
					$this->_helper->json->sendJson($response);
				}

				try {
					$this->em->persist($prov_prod);
					$this->em->flush();
				} catch (Exception $e) {
					$response['result'] = 'error';
					$response['error'] = $e->getMessage();					
					$this->log->err("An error occurred persisting the provisioned product to the DB. Error " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
					$this->_helper->json->sendJson($response);
				}
			} else {
				$response ['result'] = 'error';
				$response ['error'] = 'A product with id ' . $product_id . ' was not found';
				$this->log->err($response['error']);
			}
		}
		$this->_helper->json->sendJson($response);
	}
	
	public function cleanupAction() {
		$response = array ('result' => 'success' );
		if($this->_request->has ( 'id' )) {			
			$bootstrap = $this->getInvokeArg('bootstrap');				
			$creds = $bootstrap->getResource('cloudCredentials');			
			ClientFactory::setCredentials( $creds->rs_acct, $creds->rs_email, $creds->rs_pass );
			$api = ClientFactory::getClient();
			
			$aws = array();
			$aws[1] = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => \Guzzle\Aws\Ec2\Ec2Client::REGION_US_EAST_1));
			$aws[2] = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => \Guzzle\Aws\Ec2\Ec2Client::REGION_EU_WEST_1));
			$aws[3] = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => \Guzzle\Aws\Ec2\Ec2Client::REGION_US_WEST_1));
			$aws[4] = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => \Guzzle\Aws\Ec2\Ec2Client::REGION_AP_SOUTHEAST_1));
			$aws[5] = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => \Guzzle\Aws\Ec2\Ec2Client::REGION_AP_NORTHEAST_1));
			$aws[6] = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => \Guzzle\Aws\Ec2\Ec2Client::REGION_US_WEST_2));
			$aws[7] = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => \Guzzle\Aws\Ec2\Ec2Client::REGION_SA_EAST_1));
			
			$product_id = $this->_request->getParam ( 'id' );
			$dql = "SELECT p FROM ProvisionedProduct p WHERE p.id = " . $product_id;
			$result = $this->em->createQuery($dql)->getResult();
			
			if(count($result) == 1) {
				$keep_going = false;
				do {
					$prov_arrays = array();
					$prov_servers = array();
					$prov_depl = null;
					$prov_sshkeys = array();
					$prov_secgrps = array();					
					foreach($result[0]->provisioned_objects as $provisioned_obj) {
						if(is_a($provisioned_obj, 'ProvisionedDeployment')) {
							$prov_depl = $provisioned_obj;
						}
						if(is_a($provisioned_obj, 'ProvisionedServer')) {
							$prov_servers[] = $provisioned_obj;
						}
						if(is_a($provisioned_obj, 'ProvisionedArray')) {
							$prov_arrays[] = $provisioned_obj;
						}						
						if(is_a($provisioned_obj, 'ProvisionedSshKey')) {
							$prov_sshkeys[] = $provisioned_obj;
						}
						if(is_a($provisioned_obj, 'ProvisionedSecurityGroup')) {
							$prov_secgrps[] = $provisioned_obj;
						}
					}
					
					# Stop and destroy arrays
					if(count($prov_arrays) > 0) {
						foreach($prov_arrays as $prov_array) {
							$array = new ServerArray();
							$array->find_by_href($prov_array->href);
							if($array->active_instances_count > 0) {
								# TODO: This is a fire-and-forget terminate, then the array is deleted immediately
								# afterward
								$array->terminate_all();
							}
							$array->destroy();
							$result[0]->provisioned_objects->removeElement($prov_array);
							$this->em->remove($prov_array);
							$this->em->flush();
						}
					}
					
					# Stop and destroy the servers
					if(count($prov_servers) > 0) {
						foreach($prov_servers as $prov_server) {
							$server = new Server();
							$server->find_by_href($prov_server->href);
							if(!in_array($server->state, array('stopped', 'decomissioning'))) {
								$server->stop(true);
								$response['wait_for_decom']['servers'][] = $server->href;
							} else if($server->state == 'decomissioning') {
								$response['wait_for_decom']['servers'][] = $server->href;
							} else {
								$server->destroy();
								$result[0]->provisioned_objects->removeElement($prov_server);
								$this->em->remove($prov_server);
								$this->em->flush();
							}						
						}
					}
					
					# Wait up if we're waiting on servers or array instances					
					if(	array_key_exists('wait_for_decom', $response) && count($response['wait_for_decom']) > 0) {
						break;
					}
					
					# Destroy the deployment
					if($prov_depl) {
						$depl = new Deployment();
						$depl->find_by_href($prov_depl->href);												
						$depl->destroy();
						$result[0]->provisioned_objects->removeElement($prov_depl);
						$this->em->remove($prov_depl);
						$this->em->flush();						
					}
					
					# Destroy SSH key
					if(count($prov_sshkeys) > 0) {
						foreach($prov_sshkeys as $prov_sshkey) {
							$sshkey = new SshKey();
							$sshkey->find_by_href($prov_sshkey->href);
							$sshkey->destroy();
							$result[0]->provisioned_objects->removeElement($prov_sshkey);
							$this->em->remove($prov_sshkey);
							$this->em->flush();
						}
					}				
					
					# Destroy SecurityGroups
					if(count($prov_secgrps) > 0) {
						$group_ids = array();
						
						# Destroy the rules first
						foreach($prov_secgrps as $prov_secgrp) {
							// TODO: This is expensive, making 2 calls simply to find the group
							$sec_grp = new SecurityGroup(null);
							$sec_grp->find_by_href($prov_secgrp->href);
							
							$command = new DescribeSecurityGroups();
							$command->set('filters', array('group-name' => $sec_grp->aws_group_name));
							$grpz = $aws[$prov_secgrp->cloud_id]->execute($command);
							# Delete the group rules
							foreach($grpz->securityGroupInfo->item as $group) {
								$command = new RevokeSecurityGroupIngress();
								$command->set('group_id', (string)$group->groupId);
								$rules = array();
								foreach($group->ipPermissions->item as $rule) {
									if(count($rule->ipRanges->item) > 0) {
										$rlz = array('protocol' => $rule->ipProtocol, 'from_port' => $rule->fromPort, 'to_port' => $rule->toPort, 'cidr_ips' => $rule->ipRanges->item[0]->cidrIp);
										$rules[] = $rlz;
									}
								
									if(count($rule->groups->item) > 0){
										$rlz = array('user_id' => $rule->groups->item[0]->userId, 'group_id' => $rule->groups->item[0]->groupId, 'protocol' => $rule->ipProtocol, 'from_port' => $rule->fromPort, 'to_port' => $rule->toPort);
										$rules[] = $rlz;
									}
								}
								if(count($rules) > 0) {
									$command->set('rules', $rules);
									$aws[$prov_secgrp->cloud_id]->execute($command);
								}
								$group_ids[(string)$group->groupId] = $prov_secgrp;
							}
						}
						
						# Destroy the actual groups
						foreach($group_ids as $groupid => $prov_secgrp) {
							$command = new DeleteSecurityGroup();
							$command->set('group_id', $groupid);
							$aws[$prov_secgrp->cloud_id]->execute($command);
							
							# Remove the provisioned object DB record
							$result[0]->provisioned_objects->removeElement($prov_secgrp);
							$this->em->remove($prov_secgrp);
							$this->em->flush();
						}					
						
					}
				} while ($keep_going);
				$this->em->remove($result[0]);
				$this->em->flush();				
			}
			
			$this->_helper->json->sendJson($response);
		}
	}
	
	public function debugAction() {
		$response = array ('result' => 'success' );		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$creds = $bootstrap->getResource('cloudCredentials');
		ClientFactory::setCredentials( $creds->rs_acct, $creds->rs_email, $creds->rs_pass );
		$api = ClientFactory::getClient();
		
		$aws = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret));
		
		if($this->_request->has ( 'id' )) {				
			$product_id = $this->_request->getParam ( 'id' );
			$dql = "SELECT p FROM ProvisionedProduct p WHERE p.id = " . $product_id;
			$result = $this->em->createQuery($dql)->getResult();
				
			if(count($result) == 1) {
				$response['foobar'] = array();
				foreach($result[0]->provisioned_objects as $prov_obj) {
					$response['foobar'][] = get_class($prov_obj);
					$result[0]->provisioned_objects->removeElement($prov_obj);
					$this->em->remove($prov_obj);
					$this->em->flush();
				}
			}
						
			$this->_helper->json->sendJson($response);
		} else {
			$deplz = new Deployment();
			foreach($deplz->index() as $depl) {
				if(preg_match('/^rsss-.*/', $depl->nickname)) {
					foreach($depl->servers as $server) {
						$server->destroy();						
					}

					$arrayz = new ServerArray();
					foreach($arrayz->index() as $array) {
						if($array->deployment_href == $depl->href) {
							$array->destroy();
						}
					}
					
					$depl->destroy();
				}
			}
		}
		
		
		$regions = array();
		$regions[1] = \Guzzle\Aws\Ec2\Ec2Client::REGION_US_EAST_1;
		$regions[2] = \Guzzle\Aws\Ec2\Ec2Client::REGION_EU_WEST_1;
		$regions[3] = \Guzzle\Aws\Ec2\Ec2Client::REGION_US_WEST_1;
		$regions[4] = \Guzzle\Aws\Ec2\Ec2Client::REGION_AP_SOUTHEAST_1;
		$regions[5] = \Guzzle\Aws\Ec2\Ec2Client::REGION_AP_NORTHEAST_1;
		$regions[6] = \Guzzle\Aws\Ec2\Ec2Client::REGION_US_WEST_2;
		$regions[7] = \Guzzle\Aws\Ec2\Ec2Client::REGION_SA_EAST_1;
		
		foreach($regions as $region) {
			$aws_secgrp = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => $region));
			$response['grpz'] = array();
			$command = new DescribeSecurityGroups();
			$command->set('filters', array('group-name' => 'rsss-*'));
			$grpz = $aws_secgrp->execute($command);
			# Delete the group rules
			foreach($grpz->securityGroupInfo->item as $group) {			
				$command = new RevokeSecurityGroupIngress();
				$command->set('group_id', (string)$group->groupId);
				$rules = array();
				foreach($group->ipPermissions->item as $rule) {
					if(count($rule->ipRanges->item) > 0) {
						$rlz = array('protocol' => $rule->ipProtocol, 'from_port' => $rule->fromPort, 'to_port' => $rule->toPort, 'cidr_ips' => $rule->ipRanges->item[0]->cidrIp);
						$rules[] = $rlz;						
					}
					
					if(count($rule->groups->item) > 0){
						$rlz = array('user_id' => $rule->groups->item[0]->userId, 'group_id' => $rule->groups->item[0]->groupId, 'protocol' => $rule->ipProtocol, 'from_port' => $rule->fromPort, 'to_port' => $rule->toPort);
						$rules[] = $rlz;
					}
				}				
				if(count($rules) > 0) {
					$command->set('rules', $rules);
					$aws_secgrp->execute($command);
				}
			}
			
			# Delete the groups
			foreach($grpz->securityGroupInfo->item as $group) {				
				$command = new DeleteSecurityGroup();
				$command->set('group_id', (string)$group->groupId);
				$aws_secgrp->execute($command);
			}
		}
	
		# Delete the keys		
		$response['keyz'] = array();
		
		foreach($regions as $region) {
			$aws_keyz = \Guzzle\Aws\Ec2\Ec2Client::factory(array('access_key' => $creds->aws_key, 'secret_key' => $creds->aws_secret, 'region' => $region));
			$command = new DescribeKeyPairs();
			$command->set('filters', array('key-name' => 'rsss*'));
			$keyz = $aws_keyz->execute($command);
			foreach($keyz->keySet->item as $key) {
				$command = new DeleteKeyPair();
				$command->set('key_name', (string)$key->keyName);
				$aws_keyz->execute($command);
			}
		}
		
		$this->_helper->json->sendJson($response);
	}
	
}
