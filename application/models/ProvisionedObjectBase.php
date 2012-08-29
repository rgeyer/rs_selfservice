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

use RGeyer\Guzzle\Rs\Model\ModelBase;

/**
 * @Entity
 * @Table(name="provisioned_objects")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 * 	"server" = "ProvisionedServer",
 * 	"secgrp" = "ProvisionedSecurityGroup",
 * 	"sshkey" = "ProvisionedSshKey",
 * 	"depl" = "ProvisionedDeployment",
 * 	"array" = "ProvisionedArray",
 * 	"alert_spec" = "ProvisionedAlertSpec"
 * }) 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedObjectBase {
	/**
	 * @Id @GeneratedValue @Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	public $cloud_id;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	public $href;

  public function __construct(ModelBase $model) {
    $model_params = $model->getParameters();
    if(array_key_exists('id', $model_params)) {
      $this->id = $model->id;
    }

    if(array_key_exists('href', $model_params)) {
      $this->href = $model->href;
    }

    if(array_key_exists('cloud_id', $model_params)) {
      $this->cloud_id = $model->cloud_id;
    }
  }
}

/**
 * @Entity
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedDeployment extends ProvisionedObjectBase {

}

/**
 * @Entity
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedSecurityGroup extends ProvisionedObjectBase {

}

/**
 * @Entity
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedServer extends ProvisionedObjectBase {

}

/**
 * @Entity
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedSshKey extends ProvisionedObjectBase {

}

/**
 * @Entity
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedArray extends ProvisionedObjectBase {

}

/**
 * @Entity
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedAlertSpec extends ProvisionedObjectBase {

}