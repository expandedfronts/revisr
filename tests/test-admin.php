<?php

class RevisrAdminTest extends WP_UnitTestCase {

	/**
	 * The Revisr instance.
	 */
	protected $revisr;

	/**
	 * Set up the instance so we can run our tests.
	 */
	function setUp() {
		$this->revisr 			= revisr();
		$this->revisr->git 		= new Revisr_Git();
		$this->revisr->db 		= new Revisr_DB();
		$this->revisr->admin 	= new Revisr_Admin();
	}

	/**
	 * Tests the alert() method.
	 */
	function test_alert() {
		// Tests for a standard alert.
		Revisr_Admin::alert( __( 'test_alert' ) );
		$alert_transient = get_transient( 'revisr_alert' );
		$this->assertEquals( 'test_alert', $alert_transient );
		// Tests for an error.
		$this->revisr->admin->alert( 'test_error', true );
		$error_transient = get_transient( 'revisr_error' );
		$this->assertEquals( 'test_error', $error_transient );
	}

	/**
	 * Tests the Revisr_Admin::clear_transients() method.
	 */
	function test_clear_transients() {
		//First set a transient and make sure it exists.
		Revisr_Admin::alert( 'test_error', true );
		$transient = get_transient( 'revisr_error' );
		$this->assertEquals( 'test_error', $transient );

		// Clear the transients and make sure they're really gone.
		Revisr_Admin::clear_transients();
		$new_transient = get_transient( 'revisr_error' );
		$this->assertEquals( false, $new_transient );
	}

}
