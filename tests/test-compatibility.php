<?php

class RevisrCompatibilityTest extends WP_UnitTestCase {

	/**
	 * Tests the get_os() method.
	 */
	function test_get_os() {
		$os = Revisr_Compatibility::get_os();
		$this->assertArrayHasKey( 'code', $os );
		$this->assertArrayHasKey( 'name', $os );
	}

	/**
	 * Tests the guess_path() method.
	 */
	function test_get_path() {
		$path = Revisr_Compatibility::guess_path( 'mysql' );
		$this->assertContains( 'mysql', $path );
	}

	/**
	 * Tests the server_has_exec() method.
	 */
	function test_server_has_exec() {
		$this->assertEquals( 'true', Revisr_Compatibility::server_has_exec() );
	}

}
