<?php

namespace ApplicationTest\Application\Models;

use \RGeyer\Guzzle\Rs\Model\ModelBase;
use ProvisionedObjectBase;
use PHPUnit_Framework_TestCase;

class ModelConcreteClass extends ModelBase {
  public $last_request_params;
  public $last_request_command;

	public function __construct($mixed = null) {
    $this->_api_version = '1.5';
		parent::__construct($mixed);
	}

  public function executeCommand($command, array $params = array()) {
    $this->last_request_command = $command;
    $this->last_request_params = $params;
  }
}

class ProvisionedObjectBaseTest extends PHPUnit_Framework_TestCase {

  public function testCanInstantiateFromRsGuzzleClientModelBase() {
    $modelBase = new ModelConcreteClass();
    $modelBase->cloud_id = 'cloud_id';
    $modelBase->href = '/api/resource/1234';
    $provisionedObjectBase = new ProvisionedObjectBase($modelBase);
    $this->assertEquals($modelBase->cloud_id, $provisionedObjectBase->cloud_id);
    $this->assertEquals($modelBase->href, $provisionedObjectBase->href);
  }

  public function testCanInstantiateFromArray() {
    $array = array('cloud_id' => 'cloud_id', 'href' => '/api/resource/1234');
    $provisionedObjectBase = new ProvisionedObjectBase($array);
    $this->assertEquals($array['cloud_id'], $provisionedObjectBase->cloud_id);
    $this->assertEquals($array['href'], $provisionedObjectBase->href);
  }

}