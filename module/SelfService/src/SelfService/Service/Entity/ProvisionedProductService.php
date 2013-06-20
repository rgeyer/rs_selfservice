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

namespace SelfService\Service\Entity;

use Doctrine\ODM\MongoDB\LockMode;

class ProvisionedProductService extends BaseEntityService {

  protected $entityClass = 'SelfService\Document\ProvisionedProduct';

  /**
   * @return \SelfService\Service\Entity\UserService
   */
  protected function getUserEntityService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\UserService');
  }

  /**
   * @param array $params
   * @return \SelfService\Document\ProvisionedProduct
   */
  public function create(array $params) {
    $authSvc = $this->getServiceLocator()->get('AuthenticationService');
    $params['createdate'] = new \DateTime();
    $params['owner'] = $authSvc->getIdentity();
    return parent::create($params);
  }

  /**
   * @param $id
   * @param int $lockMode
   * @param null $lockVersion
   * @return \SelfService\Document\ProvisionedProduct
   */
  public function find($id, $lockMode = LockMode::NONE, $lockVersion = null) {
    return parent::find($id, $lockMode, $lockVersion);
  }

  /**
   * @param $id
   * @param array $params An associative array with the keys "href", "cloud_id", and "type".  "cloud_id" is optional
   *
   * @see \SelfService\Document\ProvisionedObject::__construct
   */
  public function addProvisionedObject($id, array $params) {
    $product = $this->find($id);
    $product->provisioned_objects[] = new \SelfService\Document\ProvisionedObject($params);
    $this->getDocumentManager()->persist($product);
    $this->getDocumentManager()->flush();
  }
}