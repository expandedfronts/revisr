<?php

class RevisrDBTest extends WP_UnitTestCase {

	/**
	 * The Revisr database object.
	 */
	protected $db;

	/**
	 * Initialize the database object.
	 */
	function setUp() {
		$this->db = new Revisr_DB();
	}

	/**
	 * Tests the check_port() functionality.
	 */
	function test_check_port() {
		$port = $this->db->check_port( 'localhost' );
		$this->assertEquals( false, $port );
		$new_port = $this->db->check_port( 'http://example.com:8080' );
		$this->assertNotEquals( false, $new_port );
		$this->assertEquals( '8080', $new_port );
		$no_port = $this->db->check_port( 'http://example.com/' );
		$this->assertEquals( false, $no_port );
	}

	/**
	 * Tests a database backup.
	 */
	function test_backup() {
		$this->db->backup();
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/revisr_db_backup.sql' );
	}

	/**
	 * Tests the verify_backup() function.
	 */
	function test_verify_backup() {
		$verify = $this->db->verify_backup();
		$this->assertEquals( true, $verify );
	}
}