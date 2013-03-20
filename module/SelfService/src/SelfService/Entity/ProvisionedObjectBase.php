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

namespace SelfService\Entity;

use RGeyer\Guzzle\Rs\Model\ModelBase;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="provisioned_objects")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
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
	 * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
	 * @var integer
	 */
	public $id;
	
	/**
	 * @ORM\Column(type="integer", nullable=true)
	 * @var integer
	 */
	public $cloud_id;
	
	/**
	 * @ORM\Column(type="string")
	 * @var string
	 */
	public $href;

  /**
   * @param mixed $model Either a RGeyer\Guzzle\Rs\Model\ModelBase or an array with the (optional) keys "href" and "cloud_id"
   */
  public function __construct($model) {
    $model_params = array();
    if(!is_array($model) && $model instanceof ModelBase) {
      $model_params = $model->getParameters();
    } else {
      $model_params = $model;
      $model = (object)$model;
    }
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