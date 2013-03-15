<?php
use RGeyer\Guzzle\Rs\RightScaleClient;

/*
 Copyright (c) 2012-2013 Ryan J. Geyer <me@ryangeyer.com>

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

use RGeyer\Guzzle\Rs\Model\Mc\Cloud;

use RGeyer\Guzzle\Rs\Common\ClientFactory;
use RGeyer\Guzzle\Rs\Model\Mc\ServerArray;
use RGeyer\Guzzle\Rs\Model\Mc\Server;
use RGeyer\Guzzle\Rs\Model\Mc\SshKey;
use RGeyer\Guzzle\Rs\Model\Mc\Deployment;

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
      ),
      'show' => array(
        'uri_prefix' => $this->_helper->url('show', 'provisionedproduct', 'admin'),
        'img_path' => '/images/info.png'
      )
		);
		$this->view->assign('actions', $actions);
	}
	
	public function provisionAction() {
    // TODO: Wrap EVERYTHING in a try catch.  There's some volatile stuff that
    // could throw catchable errors and result in very meaningless errors here
		$now = time();
		$response = array ('result' => 'success' );
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$creds = $bootstrap->getResource('cloudCredentials');
		
		if ($this->_request->has('id')) {

      $prov_helper = new \SelfService\ProvisioningHelper(
        $creds->rs_acct,
        $creds->rs_email,
        $creds->rs_pass,
        $this->log,
        $creds->owners
      );
			
			$product_id = $this->_request->getParam ( 'id' );
			$dql = "SELECT p FROM Product p WHERE p.id = " . $product_id;
			$result = $this->em->createQuery ( $dql )->getResult ();
			
			if (count ( $result ) == 1) {
				$api_security_groups = array();
				$api_servers = array();
				
				$product = $result[0];
				
				$this->meta_up_product($product, $this->_request);
				
				$prov_prod = new ProvisionedProduct();
				$prov_prod->createdate = new DateTime();
				$prov_prod->owner = $this->em->getRepository('User')->find(Zend_Auth::getInstance()->getIdentity()->id);
				$prov_prod->product = $product;

        # Persist and flush right away so that ->id is assigned and can be used
        $this->em->persist($prov_prod);
        $this->em->flush();

        $response['url'] = $this->_helper->url('show', 'provisionedproduct', 'admin') . '?id=' . $prov_prod->id;

        $prov_helper->setTags(array('rsss:provisioned_product_id='.$prov_prod->id));

				try {
          # Create the new deployment
					$deplname = sprintf ( "rsss-%s-%s", $product->name, $now );
          $deployment_params = array(
            'deployment[name]' => $this->_request->getParam('deployment_name', $deplname),
            'deployment[description]' => sprintf ( "Created by rs_selfservice for the '%s' product", $product->name )
          );
          $deployment = $prov_helper->provisionDeployment($deployment_params);

          $this->log->debug(sprintf("After provisioning the deployment, it's href is.. %s", $deployment->href, true));

          # Record the creation of the deployment
					$prov_depl = new ProvisionedDeployment(array('href' => $deployment->href));
					$prov_prod->provisioned_objects[] = $prov_depl;
					
					$this->log->info(sprintf("Created Deployment - Name: %s href: %s", $deplname, $deployment->href));
					
					$this->log->info("About to provision " . count($product->security_groups) . " Security Groups");
					// Create the Security Groups
					foreach ( $product->security_groups as $security_group ) {
						# Create the security group
						$secGrpBaseName = $security_group->name->getVal();
						$secGrpPrefixedName = sprintf ( "rsss-%s-%s", $secGrpBaseName, $now );
            $security_group->name->setVal($secGrpPrefixedName);
						$secGrp = $prov_helper->provisionSecurityGroup($security_group);

            if($secGrp) {
              # Record the creation of the security group
              $prov_grp = new ProvisionedSecurityGroup($secGrp);
              $prov_prod->provisioned_objects[] = $prov_grp;
            }
					}
					
					// Add the rules to all security groups.  This is done after creating each of them
          // so that rules which reference other groups can be successful.
					foreach ( $product->security_groups as $security_group ) {
            $prov_helper->provisionSecurityGroupRules($security_group);
					}
					
					$this->log->info("About to provision " . count($product->servers) . " different types of servers");
					
					foreach ( $product->servers as $server ) {
						foreach($prov_helper->provisionServer($server, $deployment) as $provisioned_model) {
              # TODO: Shouldn't have to differentiate between the return types here, bad code smell.
              if($provisioned_model instanceof RGeyer\Guzzle\Rs\Model\Mc\SshKey) {
                $prov_key = new \ProvisionedSshKey($provisioned_model);
                $prov_prod->provisioned_objects[] = $prov_key;
              }

              if($provisioned_model instanceof RGeyer\Guzzle\Rs\Model\Mc\Server ) {
                $prov_svr = new \ProvisionedServer($provisioned_model);
                $prov_prod->provisioned_objects[] = $prov_svr;
              }
            }
					}

					if ($product->launch_servers) {
            $prov_helper->launchServers();
					}
					
					/*foreach($product->arrays as $array) {
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
							'server_array[ec2_ssh_key_href]' => $this->_getSshKey($array->cloud_id->getVal(), sprintf("rsss-%s-%s", $product->name, $now), $prov_prod)->href,
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
								foreach($api_servers[$alert_spec_subject->id] as $api_server) {
									$alert_spec_subjects[] = $api_server['api']->href;
								}
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
					}*/
				} catch (Exception $e) {
					$response['result'] = 'error';
					$response['error'] = $e->getMessage();
					$this->log->err("An error occurred provisioning the product. Error: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
				}

				try {
					$this->em->persist($prov_prod);
					$this->em->flush();
				} catch (Exception $e) {
					$response['result'] = 'error';
					$response['error'] = $e->getMessage();					
					$this->log->err("An error occurred persisting the provisioned product to the DB. Error " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
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
			$client = ClientFactory::getClient('1.5');

      $cleanup_helper = new \SelfService\CleanupHelper(
        $creds->rs_acct,
        $creds->rs_email,
        $creds->rs_pass,
        $this->log
      );
			
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
              if($cleanup_helper->cleanupServerArray($prov_array)) {
                $result[0]->provisioned_objects->removeElement($prov_array);
                $this->em->remove($prov_array);
                $this->em->flush();
              } else {
                $response['wait_for_decom']['arrays'][] = $prov_array->href;
              }
						}
					}
					
					# Stop and destroy the servers
					if(count($prov_servers) > 0) {
						foreach($prov_servers as $prov_server) {
              if($cleanup_helper->cleanupServer($prov_server)) {
								$result[0]->provisioned_objects->removeElement($prov_server);
								$this->em->remove($prov_server);
								$this->em->flush();
              } else {
                $response['wait_for_decom']['servers'][] = $prov_server->href;
              }
						}
					}
					
					# Wait up if we're waiting on servers or array instances					
					if(	array_key_exists('wait_for_decom', $response) && count($response['wait_for_decom']) > 0) {
						break;
					}
					
					# Destroy the deployment
					if($prov_depl) {
            $cleanup_helper->cleanupDeployment($prov_depl);
						$result[0]->provisioned_objects->removeElement($prov_depl);
						$this->em->remove($prov_depl);
						$this->em->flush();						
					}
					
					# Destroy SSH key
					if(count($prov_sshkeys) > 0) {
						foreach($prov_sshkeys as $prov_sshkey) {
              $cleanup_helper->cleanupSshKey($prov_sshkey);
							$result[0]->provisioned_objects->removeElement($prov_sshkey);
							$this->em->remove($prov_sshkey);
							$this->em->flush();
						}
					}				
					
					# Destroy SecurityGroups
					if(count($prov_secgrps) > 0) {
            foreach($prov_secgrps as $prov_secgrp) {
              $cleanup_helper->cleanupSecurityGroupRules($prov_secgrp);
            }

            foreach($prov_secgrps as $prov_secgrp) {
              $cleanup_helper->cleanupSecurityGroup($prov_secgrp);
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

  public function showAction() {
		$bootstrap = $this->getInvokeArg('bootstrap');
		$creds = $bootstrap->getResource('cloudCredentials');
		ClientFactory::setCredentials( $creds->rs_acct, $creds->rs_email, $creds->rs_pass );
    $client = ClientFactory::getClient('1.5');

		if($this->_request->has ( 'id' )) {
			$product_id = $this->_request->getParam ( 'id' );
			$dql = "SELECT p FROM ProvisionedProduct p WHERE p.id = " . $product_id;
			$result = $this->em->createQuery($dql)->getResult();

			if(count($result) == 1) {
        $prov_servers = array();
        $prov_depl = null;
        foreach($result[0]->provisioned_objects as $provisioned_obj) {
          if(is_a($provisioned_obj, 'ProvisionedDeployment')) {
            $prov_depl = $provisioned_obj;
          }
          if(is_a($provisioned_obj, 'ProvisionedServer')) {
            $prov_servers[] = $provisioned_obj;
          }
        }

        $servers = array();

        $mc_srv_model = $client->newModel('Server');
        $mc_depl_href = \RGeyer\Guzzle\Rs\RightScaleClient::convertHrefFrom1to15($prov_depl->href);
        foreach($mc_srv_model->index($mc_depl_href) as $server) {
          $stdServer = new stdClass();
          $stdServer->name = $server->name;
          $stdServer->state = $server->state;
          $stdServer->created_at = $server->created_at;
          $stdServer->href = $server->href;

          if(!in_array($server->state, array('inactive', 'stopped'))){
            $api_server_model = $client->newModel('Server');
            $api_server_model->find_by_href($stdServer->href);

            $stdServer->ip = $api_server_model->current_instance()->public_ip_addresses[0];
          }

          $servers[] = $stdServer;
        }

        foreach($servers as $idx => $server) {
          if(!$server->actions) {
            $server->actions = array();
          }
          if(in_array($server->state, array('inactive', 'stopped'))) {
            $server->actions['start'] = array(
              'uri_prefix' => $this->_helper->url('serverstart', 'provisionedproduct', 'admin'),
              'img_path' => '/images/plus.png'
            );
          }
          if($server->state == 'operational') {
            $server->actions['stop'] = array(
              'uri_prefix' => $this->_helper->url('serverstop', 'provisionedproduct', 'admin'),
              'img_path' => '/images/delete.png'
            );
          }
        }

        $this->view->assign('servers', $servers);
      }
    }
  }

  public function serverstartAction() {
    $response = array ('result' => 'success' );
		$bootstrap = $this->getInvokeArg('bootstrap');
		$creds = $bootstrap->getResource('cloudCredentials');
		ClientFactory::setCredentials( $creds->rs_acct, $creds->rs_email, $creds->rs_pass );
    $client = ClientFactory::getClient('1.5');

		if($this->_request->has('href')) {
      $href = $this->_request->getParam('href');
      $server = $client->newModel('Server');
      $server->find_by_href($href);
      $server->launch();
    }

		$this->_helper->json->sendJson($response);
  }

  public function serverstopAction() {
    $response = array ('result' => 'success' );
		$bootstrap = $this->getInvokeArg('bootstrap');
		$creds = $bootstrap->getResource('cloudCredentials');
		ClientFactory::setCredentials( $creds->rs_acct, $creds->rs_email, $creds->rs_pass );
    $client = ClientFactory::getClient('1.5');

		if($this->_request->has('href')) {
      $href = $this->_request->getParam('href');
      $server = $client->newModel('Server');
      $server->find_by_href($href);
      $this->log->debug(print_r($server->getParameters(), true));
      $server->terminate();
    }

		$this->_helper->json->sendJson($response);
  }
	
}
