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
 * @ODM\EmbeddedDocument
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class InstanceTypeProductInput extends AbstractProductInput {

  public $resource_type = "instance_type_product_input";

  /**
   * @ODM\Hash
   * @var array
   */
  public $cloud_product_input;

  /**
   * @ODM\EmbedMany(targetDocument="CloudToResourceHref")
   * @var \SelfService\Document\CloudToResourceHref[]
   */
  public $default_value;

  /**
   * TODO: This doesn't make a lot of sense, and it is a brute force solution.
   * I was experiencing issues where actual use resulted in the cloud_product_input
   * property of this object being an associative array (hash) when accessed in
   * view/product/rendermetaform.tpl.
   *
   * However, in unit tests of the product controller, this same property was
   * appearing as an object.
   *
   * Since an object makes the most sense, we're casting to that upon fetching from
   * the DB. Chances are good other properties on other classes have similar behavior
   * which will likely result in much of my json -> odm -> json -> stdClass conversion
   * routines breaking in unpredictable ways.
   *
   * @ODM\PostLoad
   */
  public function ensureCloudProductInputIsStdClass() {
    $this->cloud_product_input = (object)$this->cloud_product_input;
  }

}