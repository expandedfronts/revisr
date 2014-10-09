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
	 * Array of user preferences and settings.
	 */
	public $options;

	/**
	 * The main git class.
	 */
	public $git;

	/**
	 * Initialize the class.
	 * @access public
	 */
	public function __construct( $options ) {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'init_settings' ) );
		}
		$this->options 	= $options;
		$this->git 		= new Revisr_Git();
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
			array( $this, 'revisr_general_settings_callback' ),
			'revisr_general_settings'
		);
		add_settings_section(
			'revisr_remote_settings',
			'Repository Settings',
			array( $this, 'revisr_remote_settings_callback' ),
			'revisr_remote_settings'
		);
		add_settings_section(
			'revisr_database_settings',
			'Database Settings',
			array( $this, 'revisr_database_settings_callback' ),
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
            __( 'Username', 'revisr' ),
            array( $this, 'username_callback' ),
            'revisr_general_settings',
            'revisr_general_settings'          
        );      
        add_settings_field(
            'email', 
            __( 'Email', 'revisr'), 
            array( $this, 'email_callback' ), 
            'revisr_general_settings', 
            'revisr_general_settings'
        );
        add_settings_field(
        	'gitignore',
        	__( 'Files/Directories to ignore', 'revisr'),
        	array( $this, 'gitignore_callback' ),
        	'revisr_general_settings',
        	'revisr_general_settings'
    	);
    	add_settings_field(
    		'automatic_backups',
    		__( 'Automatic backup schedule', 'revisr' ),
    		array( $this, 'automatic_backups_callback' ),
    		'revisr_general_settings',
    		'revisr_general_settings'
		);
    	add_settings_field(
    		'notifications',
    		__( 'Enable email notifications?', 'revisr' ),
    		array( $this, 'notifications_callback' ),
    		'revisr_general_settings',
    		'revisr_general_settings'
		);
		add_settings_field(
            'remote_name', 
            __( 'Remote Name', 'revisr'), 
            array( $this, 'remote_name_callback' ), 
            'revisr_remote_settings', 
            'revisr_remote_settings'
        );
        add_settings_field(
            'remote_url', 
            __( 'Remote URL', 'revisr'), 
            array( $this, 'remote_url_callback' ), 
            'revisr_remote_settings', 
            'revisr_remote_settings'
        );
    	add_settings_field(
    		'auto_push',
    		__( 'Automatically push new commits?', 'revisr' ),
    		array($this, 'auto_push_callback'),
    		'revisr_remote_settings',
    		'revisr_remote_settings'
		);
		add_settings_field(
			'auto_pull',
			__( 'Automatically pull new commits?', 'revisr' ),
			array($this, 'auto_pull_callback'),
			'revisr_remote_settings',
			'revisr_remote_settings'
		);
        add_settings_field(
        	'mysql_path',
        	__( 'Path to MySQL', 'revisr' ),
        	array($this, 'mysql_path_callback'),
        	'revisr_database_settings',
        	'revisr_database_settings'
    	);
    	add_settings_field(
    		'reset_db',
    		__( 'Reset database when changing branches?', 'revisr' ),
    		array($this, 'reset_db_callback'),
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
			'revisr_remote_settings'
		);
		register_setting(
			'revisr_database_settings',
			'revisr_database_settings'
		);
	}

	public function revisr_general_settings_callback() {
		_e( 'These settings configure the local repository, and may be required for Revisr to work correctly.', 'revisr' );
	}

	public function revisr_remote_settings_callback() {
		_e( 'These settings are optional, and only need to be configured if you plan to push your website to a remote repository like Bitbucket or Github.', 'revisr' );
	}

	public function revisr_database_settings_callback() {

	}		

	public function username_callback() {
		printf(
            '<input type="text" id="username" name="revisr_general_settings[username]" value="%s" class="regular-text" />
            <br><span class="description">%s</span>',
            isset( $this->options['username'] ) ? esc_attr( $this->options['username']) : '',
            __( 'The username to commit with in Git.', 'revisr' )
        );
	}

	public function email_callback() {
		printf(
            '<input type="text" id="email" name="revisr_general_settings[email]" value="%s" class="regular-text" />
            <br><span class="description">%s</span>',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : '',
            __( 'The email address associated to your Git username. Also used for notifications (if enabled).', 'revisr' )
        );
	}

	public function gitignore_callback() {
		chdir( ABSPATH );
		if ( isset( $this->options['gitignore'] ) ) {
			$gitignore = $this->options['gitignore'];
		} elseif ( file_exists( '.gitignore' ) ) {
			$gitignore = file_get_contents( '.gitignore' );
		} else {
			$gitignore = '';
		}
		printf(
            '<textarea id="gitignore" name="revisr_general_settings[gitignore]" rows="6" />%s</textarea>
            <br><span class="description">%s</span>',
            $gitignore,
            __( 'Add files or directories that you don\'t want to show up in Git here, one per line.<br>This will update the ".gitignore" file for this repository.', 'revisr' )
		);
	}

	public function automatic_backups_callback() {
		if ( isset( $this->options['automatic_backups'] ) ) {
			$schedule = $this->options['automatic_backups'];
		} else {
			$schedule = 'none';
		}
		?>
			<select id="automatic_backups" name="revisr_general_settings[automatic_backups]">
				<option value="none" <?php selected( $schedule, 'none' ); ?>><?php _e( 'None', 'revisr' ); ?></option>
				<option value="daily" <?php selected( $schedule, 'daily' ); ?>><?php _e( 'Daily', 'revisr' ); ?></option>
				<option value="weekly" <?php selected( $schedule, 'weekly' ); ?>><?php _e( 'Weekly', 'revisr' ); ?></option>
			</select>
			<span class="description"><?php _e( 'Automatic backups will backup both the files and database at the interval of your choosing.', 'revisr' ); ?></span>
		<?php
	}
	
	public function notifications_callback() {
		printf(
			'<input type="checkbox" id="notifications" name="revisr_general_settings[notifications]" %s />
			<span class="description">%s</span>',
			isset( $this->options['notifications'] ) ? "checked" : '',
			__( 'Enabling notifications will send updates about new commits, pulls, and pushes to the email address above.', 'revisr' )
		);
	}

	public function remote_name_callback() {
		printf(
			'<input type="text" id="remote_name" name="revisr_remote_settings[remote_name]" value="%s" class="regular-text" placeholder="origin" />
			<br><span class="description">%s</span>',
			isset( $this->options['remote_name'] ) ? esc_attr( $this->options['remote_name']) : '',
			__( 'Git sets this to "origin" by default when you clone a repository, and this should be sufficient in most cases.<br>If you\'ve changed the remote name or have more than one remote, you can specify that here.', 'revisr' )
		);
	}

	public function remote_url_callback() {
		$check_remote = $this->git->run( 'config --get remote.origin.url' );
		if ( isset( $this->options['remote_url'] ) && $this->options['remote_url'] != '' ) {
			$remote_url = esc_attr( $this->options['remote_url'] );
		} elseif ( $check_remote !== false ) {
			$remote_url = $check_remote[0];
		} else {
			$remote_url = '';
		}
		printf(
			'<input type="text" id="remote_url" name="revisr_remote_settings[remote_url]" value="%s" class="regular-text" placeholder="https://user:pass@host.com/user/example.git" /><span id="verify-remote"></span>
			<br><span class="description">%s</span>',
			$remote_url,
			__( 'Useful if you need to authenticate over "https://" instead of SSH, or if the remote has not already been set through Git.', 'revisr' )
		);
	}

	public function auto_push_callback() {
		printf(
			'<input type="checkbox" id="auto_push" name="revisr_remote_settings[auto_push]" %s />
			<span class="description">%s</span>',
			isset( $this->options['auto_push'] ) ? "checked" : '',
			__( 'If checked, Revisr will automatically push new commits to the remote repository.', 'revisr' )
		);
	}

	public function auto_pull_callback() {
		printf(
			'<input type="checkbox" id="auto_pull" name="revisr_remote_settings[auto_pull]" %s />
			<span class="description">%s</span>',
			isset( $this->options['auto_pull'] ) ? "checked" : '',
			__( 'Check to allow Revisr to automatically pull commits from Bitbucket or Github.', 'revisr' )
		);
		$post_hook = get_admin_url() . 'admin-post.php?action=revisr_update';
		printf( 
			__( '<br><br><span id="post-hook" class="description">You will need to add the following POST hook to Bitbucket/GitHub:<br><input id="post-hook-input" type="text" value="%s" disabled /></span>', 'revisr'), 
			$post_hook 
		);
	}

	public function mysql_path_callback() {
		printf(
			'<input type="text" id="mysql_path" name="revisr_database_settings[mysql_path]" value="%s" class="regular-text" placeholder="" />
			<br><p class="description">%s</p>',
			isset( $this->options['mysql_path'] ) ? esc_attr( $this->options['mysql_path']) : '',
			__( 'Leave blank if the full path to MySQL has already been set on the server. Some possible settings include:
			<br><br>For MAMP: /Applications/MAMP/Library/bin/<br>
			For WAMP: C:\wamp\bin\mysql\mysql5.6.12\bin\ ', 'revisr' )
		);		
	}

	public function reset_db_callback() {
		printf(
			'<input type="checkbox" id="reset_db" name="revisr_database_settings[reset_db]" %s />
			<p class="description">%s</p>',
			isset( $this->options['reset_db'] ) ? "checked" : '',
			__( 'When switching to a different branch, should Revisr automatically restore the latest database backup for that branch?<br>
			If enabled, the database will be automatically backed up before switching branches.', 'revisr' )
		);
	}

}