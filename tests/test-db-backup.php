<?php

class Revisr_DB_Backup_Test extends WP_UnitTestCase {

	/**
	 * The main Revisr instance.
	 * @var Revisr
	 */
	protected $revisr;

	/**
	 * The Revisr Backup class.
	 * @var Revisr_DB_Backup
	 */
	protected $backup;

	/**
	 * Sets up the unit tests for this class.
	 * @access public
	 */
	public function setUp() {
		$this->revisr 		= Revisr::get_instance();
		$this->revisr->git 	= new Revisr_Git;
		$this->revisr->db 	= new Revisr_DB;
		$this->backup 		= new Revisr_DB_Backup;
	}

	/**
	 * Tests the Revisr_DB_Backup->backup_table_mysql() method.
	 * @access public
	 */
	public function test_backup_table_mysql() {
		$backup = $this->backup->backup_table_mysql( 'wptests_posts' );
		$verify = $this->revisr->db->verify_backup( 'wptests_posts' );

		$this->assertEquals( true, $backup );
		$this->assertEquals( true, $verify );

	}


	/**
	 * Tests the Revisr_DB_Backup->backup_table_wpdb() method.
	 * @access public
	 */
	public function test_backup_table_wpdb() {
		$backup = $this->backup->backup_table_wpdb( 'wptests_posts' );
		$verify = $this->revisr->db->verify_backup( 'wptests_posts' );

		$this->assertEquals( true, $backup );
		$this->assertEquals( true, $verify );
	}


}
