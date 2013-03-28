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

namespace SelfService\Entity\Provisionable;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity @ORM\Table(name="arrays")
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ServerArray extends AlertSubjectBase {
	
	/**
	 * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var TextProductMetaInput
	 */
	public $nickname;
	
	/**
	 * @ORM\ManyToOne(targetEntity="ServerTemplate", fetch="EAGER", cascade={"all"})
	 * @var ServerTemplate
	 */
	public $server_template;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\NumberProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var NumberProductMetaInput
	 */
	public $min_count;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\NumberProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var NumberProductMetaInput
	 */
	public $max_count;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\CloudProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var CloudProductMetaInput
	 */
	public $cloud_id;
	
	/**
	 * This is a string, rather than an integer because the API 1.5
	 * docs suggest that a hexidecimal number is possible, and
	 * because AWS instance types are just strings (I.E. t1.micro, m1.small)
	 * 
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var TextProductMetaInput
	 */
	public $instance_type;	
	
	/**
	 * @ORM\ManyToMany(targetEntity="SecurityGroup", fetch="EAGER", cascade={"all"})
	 * @var SecurityGroup
	 */
	public $security_groups;
	
	/**
	 * Either "alert" or "queue"
	 * 
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var TextProductMetaInput
	 */
	public $type;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var TextProductMetaInput
	 */
	public $tag;
}
