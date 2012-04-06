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

class Cloudcredentials extends \Zend_Application_Resource_ResourceAbstract
{
	protected $_options = array(
		'cloudCredentials' => array(
			'rightscale' => array(
				'email' 			=> null,
				'password' 		=> null,
				'account_id' 	=> 0					
			),
			'aws' => array(
				'access_key_id' => null,
				'secret_key' => null
			)
		)
	);
	
	public $rs_email;
	public $rs_pass;
	public $rs_acct;
	public $aws_key;
	public $aws_secret;
	
	public function init() {
		$options = $this->getOptions();
		
		$this->rs_email 	= $options['rightscale']['email'];
		$this->rs_pass 		= $options['rightscale']['password'];
		$this->rs_acct 		= $options['rightscale']['account_id'];
		
		$this->aws_key 		= $options['aws']['access_key_id'];
		$this->aws_secret = $options['aws']['secret_key'];
		
		return $this;
	}
}