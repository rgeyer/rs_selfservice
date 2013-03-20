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
 * @ORM\Entity @ORM\Table(name="security_groups")
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class SecurityGroup {
	
	/**
	 * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var TextProductMetaInput
	 */
	public $name;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var TextProductMetaInput
	 */
	public $description;
	
	/**
	 * @ORM\ManyToMany(targetEntity="SecurityGroupRule", fetch="EAGER", cascade={"all"})
	 * @var SecurityGroupRule[]
	 */
	public $rules;
	
	/**
	 * @ORM\ManyToOne(targetEntity="SelfService\Entity\Provisionable\MetaInputs\CloudProductMetaInput", fetch="EAGER", cascade={"all"})
	 * @var CloudProductMetaInput
	 */
	public $cloud_id;
}