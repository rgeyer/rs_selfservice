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

/**
 * SecurityGroupController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class Admin_SecurityGroupController extends \SelfService\controller\BaseController {
	
	public function addAction() {
		if($this->_request->isPost()) {
			$params = $this->getRequest()->getParams();
			
			$errorzor = null;
			
			try {
				$security_group = new SecurityGroup();
				foreach($params['rules'] as $rule) {
					$dRule = new SecurityGroupRule();
					$dRule->ingress_protocol = $rule['protocol'];
					$dRule->ingress_from_port = $rule['from'];
					$dRule->ingress_to_port = $rule['to'];
					if($rule['type'] == "IPs") {
						$dRule->ingress_cidr_ips = $rule['cidr_ips_or_group'];
					} else {
						// TODO: Hmmn.. Need to figure out how to handle this.
						//$dRule->ingress_group = $;
						$dsql = "SELECT g FROM SecurityGroup g WHERE g.name = '" . $rule['name'];
						$result = $this->em->createQuery($dsql)->getResult();
						if($result) {
							$dRule->ingress_group = $result[0];
						} else {
							throw new Exception('The security group named (' . $rule['name'] . ') does not exist for this product.  Try creating it before adding it to another security group');						
						}
					}
					$security_group->rules[] = $dRule;
				}			
				
				$security_group->name = $params['name'];
				$security_group->description = $params['description'];
				$this->em->persist($security_group);
				$this->em->flush();
			} catch (Exception $e) {
				$errorzor = print_r($e, true);
			}
			
			$data = array(
				'name' 	=> $params['name'],
				'id'		=> $security_group->id,
				'rules' => print_r($params['rules'], true),
				'error' => $errorzor
			);
			
			$this->_helper->json->sendJson($data);
		}
	}
	
	public function delAction() {
		$data = array('result' => 'success');
		$params = $this->getRequest()->getParams();
		if($params['id']) {
			try {
				$dql = "DELETE SecurityGroup g WHERE g.id = " . $params['id'];
				$this->em->createQuery($dql)->getResult();
			} catch (Exception $e) {
				$data['result'] = 'error';
				$data['error'] = print_r($e, true);
			}
		}
		
		$this->_helper->json->sendJson($data);
	}
	
	public function editAction() {
		$this->_helper->json->sendJson(array('error' => nl2br(htmlentities(print_r($this->_request->getParams(), true)))));
	}
	
	public function addeditdialogAction() {		
		$params = $this->getRequest()->getParams();		
		if($params['id']) {
			$dql = "SELECT s FROM SecurityGroup s WHERE s.id = " . $params['id'];
			$result = $this->em->createQuery($dql)->getResult();
			$this->view->assign('secgrp', $result[0]);
		}

		$this->view->assign('secgrp_rule_del_uri', $this->_helper->url('del', 'securitygrouprule', 'admin'));
		$this->view->assign('secgrp_rule_edit_uri', $this->_helper->url('edit', 'securitygrouprule', 'admin'));
	}

}

