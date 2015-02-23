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
		$this->revisr = Revisr::get_instance();
	}

	/**
	 * Tests the revisr() method.
	 */
	function test_revisr() {
		revisr();
		$this->assertClassHasStaticAttribute( 'instance', 'Revisr' );
	}

	/**
	 * Tests the define_constants() method.
	 */
	function test_define_constants() {
		// Plugin Folder URL
		$path = str_replace( 'tests/', '', plugin_dir_url( __FILE__ ) );
		$this->assertSame( REVISR_URL, $path );

		// Plugin Folder Path
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$this->assertSame( REVISR_PATH, $path );

		// Plugin Root File
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$this->assertSame( REVISR_FILE, $path . 'revisr.php' );
	}

	/**
	 * Tests the load_dependencies() method.
	 */
	function test_load_dependencies() {
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-i18n.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-git.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-admin.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-remote.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-db.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-git-callback.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-cron.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-process.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-list-table.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-commits.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-settings.php' );
		$this->assertFileExists( REVISR_PATH . 'includes/class-revisr-settings-fields.php' );
	}

	/**
	 * Tests get_table_name().
	 */
	function test_get_table_name() {
		$revisr_table_name = Revisr::get_table_name();
		$this->assertContains( 'revisr', $revisr_table_name );
	}

	/**
	 * Tests the get_options() method.
	 */
	function test_get_options() {
		$options = Revisr::get_options();
		if ( is_array( $options ) ) {
			$result = true;
		} else {
			$result = false;
		}
		$this->assertTrue( $result );
	}

	/**
	 * Tests the settings_link() method.
	 */
	function test_settings_link() {
		$settings_links = serialize( Revisr::settings_link( $links = array() ) );
		$this->assertContains( 'revisr', $settings_links );
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
}
