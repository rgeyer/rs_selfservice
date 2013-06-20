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

use RGeyer\Guzzle\Rs\Model\ModelBase;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 */
class ProvisionedObject {

  /**
   * Unique Identifier for the object in the database
   * @ODM\Id
   * @var integer
   */
	public $id;
	
	/**
	 * @ODM\Int
	 * @var integer
	 */
	public $cloud_id;
	
	/**
	 * @ODM\String
	 * @var string
	 */
	public $href;

  /**
   * @ODM\String
   * @var string
   */
  public $type;

  /**
   * @param array An associative array with the keys "href", "cloud_id", and "type".  "cloud_id" is optional
   */
  public function __construct(array $params = array()) {
    if(array_key_exists('href', $params)) {
      $this->href = $params['href'];
    }

    if(array_key_exists('cloud_id', $params)) {
      $this->cloud_id = $params['cloud_id'];
    }

    if(array_key_exists('type', $params)) {
      $this->type = $params['type'];
    }
  }
}