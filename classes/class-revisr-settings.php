<?php
/**
 * class-revisr-settings.php
 *
 * Interacts with the WordPress Settings API.
 *
 * @package   	Revisr
 * @license   	GPLv3
 * @link      	https://revisr.io
 * @copyright 	Expanded Fronts, LLC
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;

class Revisr_Settings {

	/**
	 * The Settings callback class.
	 * @var Revisr_Settings_Fields()
	 */
	protected $settings_fields;

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct() {
		$this->settings_fields = new Revisr_Settings_Fields();
	}

	/**
	 * Initialize the settings.
	 * @access public
	 */
	public function init_settings() {
		$this->revisr_add_settings_sections();
		$this->revisr_add_settings_fields();
		$this->revisr_register_settings();
	}

	/**
	 * Registers the settings sections.
	 * @access public
	 */
	public function revisr_add_settings_sections() {
		add_settings_section(
			'revisr_general_settings',
			'General Settings',
			array( $this->settings_fields, 'revisr_general_settings_callback' ),
			'revisr_general_settings'
		);
		add_settings_section(
			'revisr_remote_settings',
			'Repository Settings',
			array( $this->settings_fields, 'revisr_remote_settings_callback' ),
			'revisr_remote_settings'
		);
		add_settings_section(
			'revisr_database_settings',
			'Database Settings',
			array( $this->settings_fields, 'revisr_database_settings_callback' ),
			'revisr_database_settings'
		);
	}

	/**
	 * Registers the settings fields.
	 * @access public
	 */
	public function revisr_add_settings_fields() {
        add_settings_field(
            'username',
            __( 'Git Username', 'revisr' ),
            array( $this->settings_fields, 'username_callback' ),
            'revisr_general_settings',
            'revisr_general_settings'
        );
        add_settings_field(
            'email',
            __( 'Git Email', 'revisr'),
            array( $this->settings_fields, 'email_callback' ),
            'revisr_general_settings',
            'revisr_general_settings'
        );
        add_settings_field(
        	'gitignore',
        	__( 'Files/Directories to ignore', 'revisr'),
        	array( $this->settings_fields, 'gitignore_callback' ),
        	'revisr_general_settings',
        	'revisr_general_settings'
    	);
    	add_settings_field(
    		'automatic_backups',
    		__( 'Automatic backup schedule', 'revisr' ),
    		array( $this->settings_fields, 'automatic_backups_callback' ),
    		'revisr_general_settings',
    		'revisr_general_settings'
		);
    	add_settings_field(
    		'notifications',
    		__( 'Enable email notifications?', 'revisr' ),
    		array( $this->settings_fields, 'notifications_callback' ),
    		'revisr_general_settings',
    		'revisr_general_settings'
		);
		add_settings_field(
            'remote_name',
            __( 'Remote Name', 'revisr'),
            array( $this->settings_fields, 'remote_name_callback' ),
            'revisr_remote_settings',
            'revisr_remote_settings'
        );
        add_settings_field(
            'remote_url',
            __( 'Remote URL', 'revisr'),
            array( $this->settings_fields, 'remote_url_callback' ),
            'revisr_remote_settings',
            'revisr_remote_settings'
        );
        add_settings_field(
        	'webhook_url',
        	__( 'Revisr Webhook URL', 'revisr' ),
        	array( $this->settings_fields, 'webhook_url_callback' ),
        	'revisr_remote_settings',
        	'revisr_remote_settings'
    	);
    	add_settings_field(
    		'auto_push',
    		__( 'Automatically push new commits?', 'revisr' ),
    		array( $this->settings_fields, 'auto_push_callback' ),
    		'revisr_remote_settings',
    		'revisr_remote_settings'
		);
		add_settings_field(
			'auto_pull',
			__( 'Automatically pull new commits?', 'revisr' ),
			array( $this->settings_fields, 'auto_pull_callback' ),
			'revisr_remote_settings',
			'revisr_remote_settings'
		);
		add_settings_field(
			'tracked_tables',
			__( 'Database tables to track', 'revisr' ),
			array( $this->settings_fields, 'tracked_tables_callback' ),
			'revisr_database_settings',
			'revisr_database_settings'
		);
    	add_settings_field(
    		'reset_db',
    		__( 'Import Options', 'revisr' ),
    		array( $this->settings_fields, 'reset_db_callback' ),
    		'revisr_database_settings',
    		'revisr_database_settings'
		);
		add_settings_field(
			'development_url',
			__( 'Development URL', 'revisr'),
			array( $this->settings_fields, 'development_url_callback' ),
			'revisr_database_settings',
			'revisr_database_settings'
		);
		add_settings_field(
			'db_driver',
			__( 'Database Driver', 'revisr' ),
			array( $this->settings_fields, 'db_driver_callback' ),
			'revisr_database_settings',
			'revisr_database_settings'
		);
        add_settings_field(
        	'mysql_path',
        	__( 'Path to MySQL', 'revisr' ),
        	array( $this->settings_fields, 'mysql_path_callback' ),
        	'revisr_database_settings',
        	'revisr_database_settings'
    	);
	}

	/**
	 * Register the settings fields with WordPress.
	 * @access public
	 */
	public function revisr_register_settings() {
		register_setting(
			'revisr_general_settings',
			'revisr_general_settings'
		);
		register_setting(
			'revisr_remote_settings',
			'revisr_remote_settings',
			array( $this, 'sanitize_remote' )
		);
		register_setting(
			'revisr_database_settings',
			'revisr_database_settings'
		);
	}

	/**
	 * Sanitizes the "Remote Settings" fields so that URL's can be stored.
	 * @access public
	 * @param  array $input The data from the form.
	 * @return array
	 */
	public function sanitize_remote( $input ) {
		if ( isset( $input['webhook_url'] ) ) {
			$input['webhook_url'] = urlencode( $input['webhook_url'] );
		}
		return $input;
	}
}
