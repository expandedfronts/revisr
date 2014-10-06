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
	 * Tests a database backup.
	 */
	function test_backup() {
		$backup = $this->db->backup();
		$this->assertFileExists( ABSPATH . '/wp-content/uploads/revisr_db_backup.sql' );
	}
}