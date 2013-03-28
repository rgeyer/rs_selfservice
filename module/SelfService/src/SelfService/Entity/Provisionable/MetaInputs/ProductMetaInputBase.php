<?php
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

namespace SelfService\Entity\Provisionable\MetaInputs;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_meta_inputs")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 * 	"cloud" = "CloudProductMetaInput",
 * 	"text" = "TextProductMetaInput",
 * 	"number" = "NumberProductMetaInput",
 *  "instance_type" = "InstanceTypeProductMetaInput",
 *  "input" = "InputProductMetaInput"
 * }) 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProductMetaInputBase {
	
	private $_val;
		
	/**
	 * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	public $input_name;
	
	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	public $display_name;
	
	/**
	 * @ORM\Column(type="string", nullable=true)
	 * @var string
	 */
	public $description;

	/**
	 * @ORM\Column(type="string")
	 * @var string
	 */
	public $default_value;
	
	public function getVal() {
		if($this->_val == null) {
			return $this->default_value;
		} else {
			return $this->_val;
		}
	}
	
	public function setVal($val) {
		$this->_val = $val;
	}
}