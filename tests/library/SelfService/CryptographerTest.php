<?php

require_once 'SelfService/Cryptographer.php';

require_once 'PHPUnit/Framework/TestCase.php';

use SelfService\Cryptographer;

/**
 * Cryptographer test case.
 */
class CryptographerTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * @var Cryptographer
	 */
	private $Cryptographer;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();
		
		// TODO Auto-generated CryptographerTest::setUp()
		

		$this->Cryptographer = new Cryptographer(/* parameters */);
	
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		// TODO Auto-generated CryptographerTest::tearDown()
		

		$this->Cryptographer = null;
		
		parent::tearDown ();
	}
	
	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// TODO Auto-generated constructor
	}
	
	/**
	 * Tests Cryptographer::getRandomKey()
	 */
	public function testGetRandomKey() {
		
		print Cryptographer::getRandomKey();
	
	}
	
	/**
	 * Tests Cryptographer::encrypt()
	 */
	public function testEncrypt() {
		// TODO Auto-generated CryptographerTest::testEncrypt()
		$this->markTestIncomplete ( "encrypt test not implemented" );
		
		Cryptographer::encrypt(/* parameters */);
	
	}
	
	/**
	 * Tests Cryptographer::decrypt()
	 */
	public function testDecrypt() {
		// TODO Auto-generated CryptographerTest::testDecrypt()
		$this->markTestIncomplete ( "decrypt test not implemented" );
		
		Cryptographer::decrypt(/* parameters */);
	
	}
	
	/**
	 * Tests Cryptographer::getKey()
	 */
	public function testGetKey() {
		// TODO Auto-generated CryptographerTest::testGetKey()
		$this->markTestIncomplete ( "getKey test not implemented" );
		
		Cryptographer::getKey(/* parameters */);
	
	}

}

