<?php
namespace Codem\DomainValidation;

/**
 * Tests for GoogleDnsOverHttps
 */
class GoogleTest extends \SapphireTest {
	
	private static $mx_domain = "codem.com.au";
	private static $a_answer = "codem.com.au.";
	private static $mx_answer = "1 aspmx.l.google.com.";
	
	public function setUp() {
		parent::setUp();
	}
	
	public function testNoMx() {
		
		$validator = new GoogleDnsOverHttps();
		$validator->setDomain("example.com");
		$answers = $validator->performLookup('MX');
		// example.com should have no MX record
		$this->assertFalse($answers);
	}
	
	/**
	 * Test that a domain has a specific MX record
	 */
	public function testValidMxMatch() {
		$validator = new GoogleDnsOverHttps();
		$validator->setDomain( self::$mx_domain );
		// Hopefully I don't change this regularly ;)
		$has = $validator->hasMxRecord( self::$mx_answer );
		$this->assertTrue($has);
	}
	
	/**
	 * Test that a domain has any MX records
	 */
	public function testValidMxAny() {
		$validator = new GoogleDnsOverHttps();
		$validator->setDomain( self::$mx_domain );
		$has = $validator->hasMxRecord();
		$this->assertTrue($has);
		
	}
	
	/**
	 * Test that a domain has any MX records
	 */
	public function testValidARecord() {
		$validator = new GoogleDnsOverHttps();
		$validator->setDomain( self::$mx_domain );
		$answers = $validator->performLookup('A');
		$this->assertTrue( is_array($answers) );
		
		$valid = false;
		foreach($answers as $answer) {
			if(isset($answer->data) && $answer->name == self::$a_answer) {
				$valid = true;
			}
		}
		$this->assertTrue($valid);
	}
	
	/**
	 * Test failure condition
	 */
	public function testInvalidRecordType() {
		$validator = new GoogleDnsOverHttps();
		$validator->setDomain( self::$mx_domain );
		$answers = $validator->performLookup('INVALID');
		var_dump($answers);
		$this->assertFalse($answers);
	}
}