<?php

class RevisrTest extends WP_UnitTestCase {

	/**
	 * The main plugin object.
	 */
	protected $revisr;

	/**
	 * Initializes the plugin.
	 */
	function setUp() {
		$this->revisr = new Revisr();
	}

	/**
	 * Tests the database installation.
	 */
	function test_revisr_install() {
		$this->revisr->revisr_install();
		global $wpdb;
		$table_name 	= $wpdb->prefix . 'revisr';
		$table_check 	= $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" );
		$this->assertEquals( $table_name, $table_check );
	}

	/**
	 * Tests check_compatibility().
	 */
	function test_check_compatibility() {
		$compatibility = $this->revisr->check_compatibility();
		$this->assertEquals( true, $compatibility );
	}

	/**
	 * Tests get_table_name().
	 */
	function test_get_table_name() {
		$revisr_table_name = Revisr::get_table_name();
		$this->assertContains( 'revisr', $revisr_table_name );
	}
}