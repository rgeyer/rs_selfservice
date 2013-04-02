<?php
/*
 Copyright (c) 2011-2013 Ryan J. Geyer <me@ryangeyer.com>

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

namespace SelfService\Entity\Provisionable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity @ORM\Table(name="products")
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class Product {
	/**
	 * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @ORM\Column(type="string")
	 * @var string
	 */
	public $name;
	
	/**
	 * The filename of the icon found in APPLICATION_PATH/images/icons
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	public $icon_filename;
	
	/**
	 * @ORM\ManyToMany(targetEntity="SecurityGroup", fetch="EAGER", cascade={"all"})
	 * @var SecurityGroup[]
	 */
	public $security_groups = array();
	
	/**
	 * @ORM\ManyToMany(targetEntity="Server", fetch="EAGER", cascade={"all"})
	 * @var Server[]
	 */
	public $servers = array();
	
	/**
	 * @ORM\ManyToMany(targetEntity="ServerArray", fetch="EAGER", cascade={"all"})
	 * @var ServerArray[]
	 */
	public $arrays = array();
	
	/**
	 * @ORM\ManyToMany(targetEntity="AlertSpec", fetch="EAGER", cascade={"all"})
	 * @var AlertSpec[]
	 */
	public $alerts = array();
	
	/**
	 * @ORM\ManyToMany(targetEntity="SelfService\Entity\Provisionable\MetaInputs\ProductMetaInputBase", fetch="EAGER", cascade={"all"})
	 * @var ProductMetaInputBase[]
	 */
	public $meta_inputs = array();
	
	/**
	 * @ORM\Column(type="boolean")
	 * @var bool
	 */
	public $launch_servers;

  /**
   * @ORM\ManyToMany(targetEntity="SelfService\Entity\Provisionable\MetaInputs\InputProductMetaInput", fetch="EAGER", cascade={"all"})
   * @var InputProductMetaInput[]
   */
	public $parameters;

  public function mergeMetaInputs(array $params) {
		$this->meta_up_object($this, $params);
		foreach($this->security_groups as $security_group) {
			$this->meta_up_object($security_group, $params);
		}

		foreach($this->servers as $server) {
			$this->meta_up_object($server, $params);
		}

		foreach($this->arrays as $array) {
			$this->meta_up_object($array, $params);
		}

		foreach($this->alerts as $alert) {
			$this->meta_up_object($alert, $params);
		}

    foreach($this->parameters as $input) {
      $this->meta_up_object($input, $params);
    }
  }

	protected function meta_up_object($object, array $params) {
		foreach(get_object_vars($object) as $var) {
			if(is_a($var, 'SelfService\Entity\Provisionable\MetaInputs\ProductMetaInputBase')
         && $var->input_name
         && in_array($var->input_name, array_keys($params))) {
				$var->setVal($params[$var->input_name]);
			}
		}
	}
}
