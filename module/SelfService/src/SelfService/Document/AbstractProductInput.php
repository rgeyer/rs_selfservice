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
 * @ODM\MappedSuperclass
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
abstract class AbstractProductInput extends CanDepend {

  /**
   * @ODM\String
   * @var string
   */
  public $id;

  /**
   * @ODM\String
   * @var string
   */
  public $input_name;

  /**
   * @ODM\String
   * @var string
   */
  public $display_name;

  /**
   * @ODM\String
   * @var string
   */
  public $description;

  /**
   * @ODM\Boolean
   * @var bool
   */
  public $display = true;

  /**
   * @ODM\Boolean
   * @var bool
   */
  public $advanced = false;

  /**
   * TODO: NOTE: This is really only useful for text_prodct_input and select_product_input.
   * It is *not* used on cloud_product_input, instance_type_product_input, or
   * datacenter_product_input since it either does not make sense, or has an
   * implied dependency.  This is enforced by the schema rather than explicitly
   * in the application logic.
   * @ODM\Hash
   * @var array
   */
  public $required_cloud_capability;

}