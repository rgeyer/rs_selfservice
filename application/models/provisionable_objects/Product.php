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
 * @Entity @Table(name="products")
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class Product {
	/**
	 * @Id @GeneratedValue @Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	public $name;
	
	/**
	 * The filename of the icon found in APPLICATION_PATH/images/icons
	 * @Column(type="string", nullable="TRUE")
	 * @var string
	 */
	public $icon_filename;
	
	/**
	 * @ManyToMany(targetEntity="SecurityGroup", fetch="EAGER", cascade={"all"})
	 * @var SecurityGroup[]
	 */
	public $security_groups;
	
	/**
	 * @ManyToMany(targetEntity="Server", fetch="EAGER", cascade={"all"})
	 * @var Server[]
	 */
	public $servers;
	
	/**
	 * @ManyToMany(targetEntity="ServerArray", fetch="EAGER", cascade={"all"})
	 * @var ServerArray[]
	 */
	public $arrays;
	
	/**
	 * @ManyToMany(targetEntity="AlertSpec", fetch="EAGER", cascade={"all"})
	 * @var AlertSpec[]
	 */
	public $alerts;	
	
	/**
	 * @ManyToMany(targetEntity="ProductMetaInputBase", fetch="EAGER", cascade={"all"})
	 * @var ProductMetaInputBase[]
	 */
	public $meta_inputs;
	
	public $parameters;
}
