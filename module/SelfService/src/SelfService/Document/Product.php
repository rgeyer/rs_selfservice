<?php
/*
 Copyright (c) 2013 Ryan J. Geyer <me@ryangeyer.com>

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

namespace SelfService\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class Product {

  /**
   * Unique Identifier for the object in the database
   * @ODM\Id
   * @var integer
   */
  public $id;

  /**
   * Semantic schema version
   * @ODM\String
   * @var string
   */
  public $version = "1.0.0";

  /**
   * Display name of the product in the RSSS UI
   * @ODM\String
   * @var string
   */
  public $name;

  /**
   * The filename of the icon found in public/images/icons
   * @ODM\String
   * @var string
   */
  public $icon_filename;

  /**
   * A boolean indicating if the servers should be launched immediately after
   * being created by the RSSS
   * @ODM\Boolean
   * @var bool
   */
  public $launch_servers;

  /**
   * @ODM\EmbedMany(
   *  discriminatorField="resource_type",
   *  discriminatorMap={
   *    "deployment"="Deployment",
   *    "input"="Input",
   *    "security_group"="SecurityGroup",
   *    "server"="Server",
   *    "alert_spec"="AlertSpec"
   *  }
   * )
   *
   *
   *    "instance"="Instance",
   *    "server_array"="ServerArray",
   *    "elasticity_params"="ElasticityParams",
   *    "server_template"="ServerTemplate",
   *    "text_product_input"="TextProductInput",
   *    "select_product_input"="SelectProductInput",
   *    "cloud_product_input"="CloudProductInput",
   *    "instance_type_product_input"="InstanceTypeProductInput",
   *    "datacenter_product_input"=DatacenterProductInput",
   * @var (Deployment|Input|Instance|SecurityGroup|Server|ServerArray|ElasticityParams|ServerTemplate|TextProductInput|SelectProductInput|CloudProductInput|InstanceTypeProductInput|DatacenterProductInput|AlertSpec)[]
   */
  public $resources;
}