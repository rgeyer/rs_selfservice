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

namespace SelfService\Provisioner;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class AbstractProvisioner
 * @package SelfService\Provisioner
 */
abstract class AbstractProvisioner implements ServiceLocatorAwareInterface {

  /**
   * An array of messages generated during either the provision or cleanup actions
   * @var String[]
   */
  protected $messages = array();

  /**
   * @var ServiceLocatorInterface
   */
  protected $serviceLocator;

  /**
   * @return ServiceLocatorInterface
   */
  public function getServiceLocator() {
    return $this->serviceLocator;
  }

  /**
   * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
   * @return void
   */
  public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
    $this->serviceLocator = $serviceLocator;
  }

  /**
   * @return \Zend\Log\Logger
   */
  protected function getLogger() {
    return $this->getServiceLocator()->get('logger');
  }

  /**
   * @return \SelfService\Service\Entity\ProvisionedProductService
   */
  protected function getProvisionedProductService() {
    return $this->getServiceLocator()->get('SelfService\Service\Entity\ProvisionedProductService');
  }

  /**
   * @param $message A message to be displayed to the API caller or UI user
   */
  protected function addMessage($message) {
    $this->messages[] = $message;
  }

  /**
   * @param bool $clear If set the messages will be fetched and cleared.
   * @return String[] An array of messages created during either provision or cleanup
   * to be displayed to the API caller or UI user
   */
  public function getMessages($clear = false) {
    if($clear) { $this->clearMessages(); }
    return $this->messages;
  }

  /**
   * Removes all current messages
   */
  public function clearMessages() {
    unset($this->messages);
    $this->messages = array();
  }

  /**
   * Concrete classes implementing this are expected to record provisioned objects under the provisioned product
   * with the ID passed in as $provisioned_product_id.  Implementers may either use the ProvisionedProduct service
   * or the API to add provisioned objects.
   *
   * = Provisioner best practices
   *
   * * provide unique names for security groups since duplicates are not allowed. I.E. <productname>-<timestamp>
   * * tag created items with the provisioned product id
   *
   * @abstract
   * @param $provisioned_product_id The unique ID of a provisioned product
   * @param $json A json string which is compliant with the json/output/schema.json schema, used to describe
   *  a product to be provisioned.
   */
  public abstract function provision($provisioned_product_id, $json);

  /**
   * @abstract
   * @param $provisioned_product_id The unique ID of a provisioned product
   * @param $json A json string which represents an array of \SelfService\Document\ProvisionedObject
   */
  public abstract function cleanup($provisioned_product_id, $json);
}