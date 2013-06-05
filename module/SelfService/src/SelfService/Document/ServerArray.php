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
 * @IgnoreAnnotation("OnlyOne")
 * @ODM\EmbeddedDocument
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class ServerArray extends AbstractResource {

  public $resource_type = "server_array";

  /**
   * @ODM\String
   * @var string
   */
  public $array_type;

  /**
   * @ODM\Hash
   * @var array|string
   */
  public $name;

  /**
   * @ODM\Hash
   * @var array|string
   */
  public $description;

  /**
   * @OnlyOne A hint to the RSSS ODMToStdClass (and subsequently JSON) methods
   * that only one of these values should be present in the output
   * @ODM\Collection
   * @var array
   */
  public $elasticity_params;

  /**
   * @OnlyOne A hint to the RSSS ODMToStdClass (and subsequently JSON) methods
   * that only one of these values should be present in the output
   * @ODM\Collection
   * @var array
   */
  public $instance;

  /**
   * @ODM\Boolean
   * @var bool
   */
  public $optimized;

  /**
   * @ODM\String
   * @var string
   */
  public $state = "disabled";

  /**
   * @ODM\Collection
   * @var array
   */
  public $alert_specs;
}