<?php

use SelfService\ProvisioningHelper;

class ProvisioningHelperTest extends PHPUnit_Framework_TestCase {

  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
  }

  /**
   * Returns a SelfService\ProvisioningHelper which has been bootstrapped with a mock login for
   * both the Ec2 and Mc APIs
   * @return SelfService\ProvisioningHelper
   */
  private function getProvisioningHelper() {
    $helper = new ProvisioningHelper('foo','bar','baz');

    return $helper;
  }

}