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

namespace SelfService\Controller;

/**
 * IndexController
 * 
 * @author Ryan J. Geyer <me@ryangeyer.com>
 */
class IndexController extends BaseController {

  /**
   * @return \SelfService\Service\Entity\ProductService
   */
  protected function getProductEntityService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\ProductService');
  }
	
	private $_noAuthRequired;
	
	public function indexAction() {
    $products = $this->getProductEntityService()->findAll();

		foreach ( $products as $product ) {
			$product->img_url = "images/icons/" . $product->icon_filename;
		}

		return array( 'products' => $products, 'use_layout' => true );
	}

  public function adminindexAction() {
    return array('use_layout' => true);
  }

}

