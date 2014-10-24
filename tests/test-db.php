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
	 * Tests the check_port() function.
	 */
	function test_check_port() {
		$port 		= $this->db->check_port( 'localhost' );
		$new_port 	= $this->db->check_port( 'http://example.com:8080' );
		$no_port 	= $this->db->check_port( 'http://example.com/' );

		$this->assertEquals( false, $port );
		$this->assertNotEquals( false, $new_port );
		$this->assertEquals( '8080', $new_port );
		$this->assertEquals( false, $no_port );
	}

	/**
	 * Tests the build_connection() function.
	 */
	function test_build_connection() {
		$conn = $this->db->build_conn();
		$this->assertNotEquals( null, $conn );
		$this->assertContains( '--host', $conn );
	}

	/**
	 * Tests a database backup.
	 */
	function test_backup() {
		$this->db->backup();
		$this->assertFileExists( ABSPATH . 'wp-content/uploads/revisr-backups/.htaccess' );
		$this->assertFileExists( ABSPATH . 'wp-content/uploads/revisr-backups/index.php' );
		$this->assertFileExists( ABSPATH . 'wp-content/uploads/revisr-backups/revisr_wptests_posts.sql' );
	}

	/**
	 * Tests a database import.
	 */
	function test_import() {
		$import = $this->db->import_table( 'wptests_users' );
		$this->assertEquals( true, $import );
	}

	/**
	 * Tests the verify_backup() function.
	 */
	function test_verify_backup() {
		$verify = $this->db->verify_backup( 'wptests_posts' );
		$this->assertEquals( true, $verify );
	}
}