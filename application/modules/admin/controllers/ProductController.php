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

use RGeyer\Guzzle\Rs\Model\Cloud;

use RGeyer\Guzzle\Rs\Common\ClientFactory;

/**
 * ProductController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */

class Admin_ProductController extends \SelfService\controller\BaseController {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$dql = "SELECT p FROM Product p";
		$query = $this->em->createQuery($dql);
		$result = $query->getResult();
		
		foreach($result as $product) {
			$product->img_url = "/images/icons/" . $product->icon_filename;
		}
		
		$this->view->assign('products', $result);
		
		$actions = array(
			'del' => array(
				'uri_prefix' => $this->_helper->url('del', 'product', 'admin'),
				'img_path' => '/images/delete.png'
			),
			'edit' => array(
				'uri_prefix' => $this->_helper->url('edit', 'product', 'admin'),
				'img_path' => '/images/pencil.png'
			)
		);
		$this->view->assign('actions', $actions);
	}
	
	public function addAction() {
		$product_dialog_url = $this->_helper->url('addeditdialog', 'product', 'admin');
		$this->view->assign('product_dialog_url', $product_dialog_url);
		if($this->_request->isPost()) {
			$params = $this->getRequest()->getParams();

			$upload_path = APPLICATION_PATH . '/../public/images/icons';			
			if(!is_dir($upload_path)) {
				mkdir($upload_path);
			}

			$product = new Product();
			// We're not using the Zend_File_Transfer_Adapter_Http here because it
			// doesn't give us the control necessary to custom name the file etc.
			if($_FILES['icon']) {
				$uniqid = uniqid();
				$filename = preg_replace('/.+?\.([a-zA-Z]*)$/', $uniqid . '.${1}', $_FILES['icon']['name']);
				
				copy($_FILES['icon']['tmp_name'], $upload_path . '/' . $filename);
				$product->icon_filename = $filename;
			}			
			
			$product->name = $params['name'];
			if($params['secgrp']) {			
				foreach($params['secgrp'] as $secgrp_id) {
					$dql = "SELECT g FROM SecurityGroup g WHERE g.id = " . $secgrp_id;
					$group = $this->em->createQuery($dql)->getResult();
					$product->security_groups[] = $group[0];
				}			
			}
			$this->em->persist($product);
			$this->em->flush();
		}
	}
	
	public function editAction() {
		$product_dialog_url = $this->_helper->url('addeditdialog', 'product', 'admin');
		$this->view->assign('product_dialog_url', $product_dialog_url);
		$this->view->assign('id', $this->getRequest()->getParam('id'));
		if($this->_request->isPost()) {
			
		}
	}
	
	public function addeditdialogAction() {		
		$secgrp_uri = $this->_helper->url('addeditdialog', 'securitygroup', 'admin');
		$this->view->assign('secgrp_uri', $secgrp_uri);
		$secgrp_add_uri = $this->_helper->url('add', 'securitygroup', 'admin');
		$this->view->assign('secgrp_add_uri', $secgrp_add_uri);
		$secgrp_del_uri = $this->_helper->url('del', 'securitygroup', 'admin');
		$this->view->assign('secgrp_del_uri', $secgrp_del_uri);
		
		$params = $this->getRequest()->getParams();
		if($params['id']) {
			try {
				$dql = "SELECT p FROM Product p WHERE p.id = " . $params['id'];
				$result = $this->em->createQuery($dql)->getResult();
				$this->view->assign('product', $result[0]);
			} catch (Exception $e) {
				$this->view->assign('messages', array(array('class' => 'error', 'text' => print_r($e, true))));
			}
		}
	}
	
	public function delAction() {		
		$dql = "SELECT p FROM Product p WHERE p.id = " . $this->getRequest()->getParam('id');
		$query = $this->em->createQuery($dql);
		$result = $query->getResult();
		
		if(count($result) == 1) {
			unlink(APPLICATION_PATH . '/../public/images/icons/' . $result[0]->icon_filename);
		}
				
		$dql = "DELETE Product p WHERE p.id = " . $this->getRequest()->getParam('id');
		$this->em->createQuery($dql)->getResult();
		$this->_helper->redirector('index', 'product', 'admin');		
	}
	
	public function rendermetaformAction() {
		$dql = "SELECT p FROM Product p WHERE p.id = " . $this->getRequest()->getParam('id');
		$query = $this->em->createQuery($dql);
		$result = $query->getResult();
		
		$bootstrap = $this->getInvokeArg('bootstrap');
		$creds = $bootstrap->getResource('cloudCredentials');
		
		ClientFactory::setCredentials( $creds->rs_acct, $creds->rs_email, $creds->rs_pass );
		$api = ClientFactory::getClient('1.5');
		
		$clouds = array(
			'AWS US-East' 			=> 1,
			'AWS US-West' 			=> 3,
			'AWS EU' 						=> 2,
			'AWS AP-Singapore' 	=> 4,
			'AWS AP-Tokyo' 			=> 5,
			'AWS SA-Sao Paulo' 	=> 7,
			'AWS US-Oregon' 		=> 6
		);
		
		$cloud_obj = new Cloud();
		$other_clouds = array();
		
		foreach($cloud_obj->index() as $cloud) {
			$other_clouds[$cloud->name] = $cloud->id;
		}
		
		$clouds = array_merge($clouds, $other_clouds);		
		
		$this->view->assign('clouds', $clouds);
		$this->view->assign('meta_inputs', $result[0]->meta_inputs);
		$this->view->assign('id', $result[0]->id);
	}

}
