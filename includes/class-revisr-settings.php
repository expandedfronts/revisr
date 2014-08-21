<?php
/**
 * class-revisr-settings.php
 *
 * Interacts with the WordPress Settings API.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts, LLC
 */

class Revisr_Settings
{
   /**
    * User options & preferences.
    * @var string
    */
	public $options;


	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'init_settings' ) );
		}
		$this->options = get_option( 'revisr_settings' );
	}
	
	/**
	 * Initializes the settings sections.
	 * @access public
	 */
	public function init_settings() {
        add_settings_section(
            'revisr_general_settings',
            '',
            array( $this, 'general_settings_callback' ),
            'revisr_settings'
        );

        add_settings_field(
            'username',
            'Username',
            array( $this, 'username_callback' ),
            'revisr_settings',
            'revisr_general_settings'          
        );      

        add_settings_field(
            'email', 
            'Email', 
            array( $this, 'email_callback' ), 
            'revisr_settings', 
            'revisr_general_settings'
        );

        add_settings_field(
        	'gitignore',
        	'Files / Directories to add to .gitignore',
        	array( $this, 'gitignore_callback'),
        	'revisr_settings',
        	'revisr_general_settings'
    	);

    	add_settings_field(
    		'notifications',
    		'Enable email notifications?',
    		array($this, 'notifications_callback'),
    		'revisr_settings',
    		'revisr_general_settings'
		);

		register_setting(
			'revisr_option_group',
			'revisr_settings'
		);

        add_settings_section(
            'revisr_remote_settings',
            '',
            array( $this, 'remote_settings_callback' ),
            'revisr_settings'
        );

        add_settings_field(
            'remote_name', 
            'Remote Name', 
            array( $this, 'remote_name_callback' ), 
            'revisr_settings', 
            'revisr_remote_settings'
        );

        add_settings_field(
            'remote_url', 
            'Remote URL', 
            array( $this, 'remote_url_callback' ), 
            'revisr_settings', 
            'revisr_remote_settings'
        );

    	add_settings_field(
    		'auto_push',
    		'Automatically push new commits?',
    		array($this, 'auto_push_callback'),
    		'revisr_settings',
    		'revisr_remote_settings'
		);

		add_settings_field(
			'auto_pull',
			'Automatically pull new commits?',
			array($this, 'auto_pull_callback'),
			'revisr_settings',
			'revisr_remote_settings'
		);

		register_setting(
			'revisr_remote_settings',
			'revisr_settings'
		);

        add_settings_section(
            'revisr_database_settings',
            '',
            array( $this, 'database_settings_callback' ),
            'revisr_settings'
        );

        add_settings_field(
        	'mysql_path',
        	'Path to MySQL',
        	array($this, 'mysql_path_callback'),
        	'revisr_settings',
        	'revisr_database_settings'
    	);

    	add_settings_field(
    		'reset_db',
    		'Reset database when changing branches?',
    		array($this, 'reset_db_callback'),
    		'revisr_settings',
    		'revisr_database_settings'
		);	

		register_setting(
			'revisr_database_settings',
			'revisr_settings'
		);			
	}

   	/**
     * Add the settings callbacks.
     * @access public
     */
	public function general_settings_callback() {
		?>
		<hr>
		<br>
		<h3><?php _e( 'General Settings', 'revisr' ); ?></h3>
		<?php
	}

	public function username_callback() {
		printf(
            '<input type="text" id="username" name="revisr_settings[username]" value="%s" class="regular-text" />
            <br><span class="description">Username to commit with in git.</span>',
            isset( $this->options['username'] ) ? esc_attr( $this->options['username']) : ''
        );
	}

	public function email_callback() {
		printf(
            '<input type="text" id="email" name="revisr_settings[email]" value="%s" class="regular-text" />
            <br><span class="description">Used for notifications and git.</span>',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : ''
        );
	}

	public function gitignore_callback() {
		printf(
            '<textarea id="gitignore" name="revisr_settings[gitignore]" rows="6" />%s</textarea>
            <br><span class="description">Add files or directories to be ignored here, one per line.</span>',
            isset( $this->options['gitignore'] ) ? esc_attr( $this->options['gitignore']) : ''
		);
	}
	
	public function notifications_callback() {
		printf(
			'<input type="checkbox" id="notifications" name="revisr_settings[notifications]" %s />
			<p class="description">Will be sent to the email address above.</p>',
			isset( $this->options['notifications'] ) ? "checked" : ''
		);
	}
	
	public function remote_settings_callback() {
		?>
		<hr>
		<br>
		<h3><?php _e( 'Remote Settings', 'revisr' ); ?></h3>
		<?php
	}

	public function remote_name_callback() {
		printf(
			'<input type="text" id="remote_name" name="revisr_settings[remote_name]" value="%s" class="regular-text" placeholder="origin" />
			<br><span class="description">Set this to "origin" unless you have changed this previously in Git. Used for pushing to and pulling from remotes.</span>',
			isset( $this->options['remote_name'] ) ? esc_attr( $this->options['remote_name']) : ''
			);
	}

	public function remote_url_callback() {
		printf(
			'<input type="text" id="remote_url" name="revisr_settings[remote_url]" value="%s" class="regular-text" placeholder="https://user:pass@host.com/user/example.git" />
			<br><span class="description">Useful if you need to authenticate over "https://" instead of SSH, or if the remote has not already been set through Git.</span>',
			isset( $this->options['remote_url'] ) ? esc_attr( $this->options['remote_url']) : ''
			);
	}

	public function auto_push_callback() {
		printf(
			'<input type="checkbox" id="auto_push" name="revisr_settings[auto_push]" %s />
			<p class="description">If checked, Revisr will automatically push new commits to the remote repository.</p>',
			isset( $this->options['auto_push'] ) ? "checked" : ''
			);
	}

	public function auto_pull_callback() {
		printf(
			'<input type="checkbox" id="auto_pull" name="revisr_settings[auto_pull]" %s />
			<p class="description">Check to allow Revisr to automatically pull commits from Bitbucket or Github.<br>
			You will need to add the following POST hook to Bitbucket/Github:<br>
			' . get_admin_url() . 'admin-post.php?action=revisr_update</p>',
			isset( $this->options['auto_pull'] ) ? "checked" : ''
			);
	}

	public function database_settings_callback() {
		?>
		<hr>
		<br>
		<h3><?php _e( 'Database Settings', 'revisr' ); ?></h3>
		<?php
	}

	public function mysql_path_callback() {
		printf(
			'<input type="text" id="mysql_path" name="revisr_settings[mysql_path]" value="%s" class="regular-text" placeholder="" />
			<br><p class="description">Leave blank if the full path to MySQL has already been set on the server. Some possible settings include:
			<br><br>For MAMP: /Applications/MAMP/Library/bin/
			<br>For WAMP: C:\wamp\bin\mysql\mysql5.6.12\bin\</p>',
			isset( $this->options['mysql_path'] ) ? esc_attr( $this->options['mysql_path']) : ''
			);		
	}

	public function reset_db_callback() {
		printf(
			'<input type="checkbox" id="reset_db" name="revisr_settings[reset_db]" %s />
			<p class="description">When switching to a different branch, should Revisr automatically restore the latest database backup for that branch?<br>
			If enabled, the database will be automatically backed up before switching branches.</p>',
			isset( $this->options['reset_db'] ) ? "checked" : ''
		);
	}
}
