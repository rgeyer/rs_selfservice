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

namespace SelfService\Controller;

use Zend\View\Model\JsonModel;

class MetaInputController extends BaseController {

  /**
   * @return \SelfService\Service\RightScaleAPICache
   */
  protected function getRightScaleApiCache() {
    return $this->getServiceLocator()->get('RightScaleAPICache');
  }

  public function instancetypesAction() {
    $client = $this->getRightScaleApiCache();
    $instance_types = $client->getInstanceTypes($this->params('id'));
    $retval = array();
    foreach($instance_types as $itype) {
      $thisclass = new \stdClass();
      $thisclass->href = $itype->href;
      $thisclass->name = $itype->name;
      $retval[] = $thisclass;
    }

    return new JsonModel(array('instance_types' => $retval, 'instance_type_ids' => $this->params()->fromPost('instance_type_ids')));
  }

  public function datacentersAction() {
    $client = $this->getRightScaleApiCache();
    $datacenters = $client->getDatacenters($this->params('id'));
    $retval = array();
    foreach($datacenters as $dc) {
      $thisclass = new \stdClass();
      $thisclass->href = $dc->href;
      $thisclass->name = $dc->name;
      $retval[] = $thisclass;
    }

    return new JsonModel(array('datacenters' => $retval, 'datacenter_ids' => $this->params()->fromPost('datacenter_ids')));
  }

}