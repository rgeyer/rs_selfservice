<?php

class ZendAuthAdaptherMock implements \Zend_Auth_Adapter_Interface {
  public function authenticate() {
    $user = new stdClass();
    $user->oid_url = 'oid_url';
    $user->email = 'email@domain.com';
    $user->name = 'anonymous';
    return new \Zend_Auth_Result(\Zend_Auth_Result::SUCCESS, $user);
  }
}