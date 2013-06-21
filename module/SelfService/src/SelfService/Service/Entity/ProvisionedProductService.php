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
use SelfService\Document\Exception\NotFoundException;

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
   * {@inheritdoc}
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
   * @throws \SelfService\Document\Exception\NotFoundException When the specified document
   *  does not exist.
   */
  public function addProvisionedObject($id, array $params) {
    $product = $this->find($id);
    $product->provisioned_objects[] = new \SelfService\Document\ProvisionedObject($params);
    $this->getDocumentManager()->persist($product);
    $this->getDocumentManager()->flush();
  }

  /**
   * @param $provisioned_product_id
   * @param $provisioned_object_id
   * @throws \SelfService\Document\Exception\NotFoundException When the specified document
   *  does not exist.
   */
  public function removeProvisionedObject($provisioned_product_id, $provisioned_object_id) {
    $product = $this->find($provisioned_product_id);
    foreach($product->provisioned_objects as $provisioned_object) {
      if($provisioned_object->id == $provisioned_object_id) {
        $product->provisioned_objects->removeElement($provisioned_object);
      }
    }
    if($product->provisioned_objects->isDirty()) {
      $this->getDocumentManager()->persist($product);
      $this->getDocumentManager()->flush();
    } else {
      throw new NotFoundException("SelfService\Document\ProvisionedObject", $provisioned_object_id,
        $this->entityClass, $provisioned_product_id
      );
    }
  }
}