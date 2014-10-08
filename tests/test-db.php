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
		$this->assertNotEquals( false, $port );
		$this->assertEquals( '8889', $port );
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
		$backup = $this->db->backup();
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/revisr_db_backup.sql' );
	}
}