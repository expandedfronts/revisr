<?php

class RevisrRemoteTest extends WP_UnitTestCase {

	/**
	 * Sets up the test instance.
	 */
	function setUp() {
		$this->remote = new Revisr_Remote();
	}

	/**
	 * Tests the "get_token()" method.
	 */
	function test_get_token() {
		$token = $this->remote->get_token();

		if ( is_string( $token ) && strlen( $token ) === 16 ) {
			$result = true;
		} else {
			$result = false;
		}

		$this->assertTrue( $result );
	}

	/**
	 * Tests the "check_token()" method.
	 */
	function test_check_token() {
		$token 	= $this->remote->get_token();
		$result = $this->remote->check_token( $token );
		$this->assertTrue( $result );
	}
}