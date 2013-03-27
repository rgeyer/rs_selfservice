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

use SelfService\Zend\Log\Writer\Collection as CollectionWriter;

class CacheController extends BaseController {

  public function updaterightscaleAction() {
    $collection_writer = new CollectionWriter();
    $this->getLogger()->addWriter($collection_writer);

    $cache_service = $this->getServiceLocator()->get('CacheService');
    $this->getLogger()->info("Updating cache data for clouds");
    $cache_service->updateClouds();
    $this->getLogger()->info("Updating cache data for instance types");
    $cache_service->updateInstanceTypes();
    $this->getLogger()->info("Updating cache data for datacenters");
    $cache_service->updateDatacenters();
    $this->getLogger()->info("Updating cache data for server templates");
    $cache_service->updateServerTemplates();

    if($this->getRequest() instanceof \Zend\Http\Request) {
      return array('messages' => $collection_writer->messages);
    } else {
      return join("\n",$collection_writer->messages)."\n";
    }
  }

}