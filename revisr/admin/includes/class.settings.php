<?php

class RevisrSettings
{
   /**
    * User options & preferences.
    * @var string
    */
	public $options;


	public function __construct()
	{
		if ( is_admin() ){
			add_action( 'admin_init',array($this, 'settings_init') );
			add_action( 'load-revisr-settings', array($this, 'update_settings') );
		}
		$this->options = get_option('revisr_settings');
	}

	public function settings_init()
	{
		register_setting(
			'revisr_option_group',
			'revisr_settings',
			array($this, 'sanitize')
		);

        add_settings_section(
            'revisr_general_config', // ID
            '', // Title
            array( $this, 'general_config_callback' ), // Callback
            'revisr_settings' // Page
        );  

        add_settings_field(
            'username', // ID
            'Username', // Title 
            array( $this, 'username_callback' ), // Callback
            'revisr_settings', // Page
            'revisr_general_config' // Section           
        );      

        add_settings_field(
            'email', 
            'Email', 
            array( $this, 'email_callback' ), 
            'revisr_settings', 
            'revisr_general_config'
        );

        add_settings_field(
            'remote_url', 
            'Remote URL', 
            array( $this, 'remote_url_callback' ), 
            'revisr_settings', 
            'revisr_general_config'
        );

        add_settings_field(
        	'gitignore',
        	'Files / Directories to add to .gitignore',
        	array( $this, 'gitignore_callback'),
        	'revisr_settings',
        	'revisr_general_config'
    	);

    	add_settings_field(
    		'reset_db',
    		'Reset database when changing branches?',
    		array($this, 'reset_db_callback'),
    		'revisr_settings',
    		'revisr_general_config'
		);

    	add_settings_field(
    		'auto_push',
    		'Automatically push new commits?',
    		array($this, 'auto_push_callback'),
    		'revisr_settings',
    		'revisr_general_config'
		);		

    	add_settings_field(
    		'revisr_admin_bar',
    		'Show pending files in admin bar?',
    		array($this, 'admin_bar_callback'),
    		'revisr_settings',
    		'revisr_general_config'
		);

    	add_settings_field(
    		'notifications',
    		'Enable email notifications?',
    		array($this, 'notifications_callback'),
    		'revisr_settings',
    		'revisr_general_config'
		);

	}

	public function general_config_callback()
	{
		//print "Enter your settings below:";
	}

	public function username_callback()
	{
		printf(
            '<input type="text" id="username" name="revisr_settings[username]" value="%s" class="regular-text" />
            <br><span class="description">Username to commit with in git.</span>',
            isset( $this->options['username'] ) ? esc_attr( $this->options['username']) : ''
        );
	}

	public function email_callback()
	{
		printf(
            '<input type="text" id="email" name="revisr_settings[email]" value="%s" class="regular-text" />
            <br><span class="description">Used for notifications and git.</span>',
            isset( $this->options['email'] ) ? esc_attr( $this->options['email']) : ''
        );
	}

	public function remote_url_callback()
	{
		printf(
			'<input type="text" id="remote_url" name="revisr_settings[remote_url]" value="%s" class="regular-text" placeholder="https://user:pass@host.com/user/example.git" />
			<br><span class="description">Optional. Useful if you need to authenticate over "https://" instead of SSH, or if the remote has not been set.</span>',
			isset( $this->options['remote_url'] ) ? esc_attr( $this->options['remote_url']) : ''
			);
	}

	public function gitignore_callback()
	{
		printf(
            '<textarea id="gitignore" name="revisr_settings[gitignore]" rows="6" />%s</textarea>
            <br><span class="description">Add files or directories to be ignored here, one per line.</span>',
            isset( $this->options['gitignore'] ) ? esc_attr( $this->options['gitignore']) : ''
		);
	}

	public function notifications_callback()
	{
		printf(
			'<input type="checkbox" id="notifications" name="revisr_settings[notifications]" %s />',
			isset( $this->options['notifications'] ) ? "checked" : ''
		);
	}

	public function admin_bar_callback()
	{
		printf(
			'<input type="checkbox" id="revisr_admin_bar" name="revisr_settings[revisr_admin_bar]" %s />',
			isset( $this->options['revisr_admin_bar'] ) ? "checked" : ''
		);
	}

	public function reset_db_callback()
	{
		printf(
			'<input type="checkbox" id="reset_db" name="revisr_settings[reset_db]" %s />
			<p class="description">When switching to a different branch, should Revisr automatically restore the latest database backup for that branch?<br>
			If enabled, the database will be automatically backed up before switching branches.</p>',
			isset( $this->options['reset_db'] ) ? "checked" : ''
		);
	}

	public function auto_push_callback()
	{
		printf(
			'<input type="checkbox" id="auto_push" name="revisr_settings[auto_push]" %s />
			<p class="description">If checked, Revisr will automatically push commits and .gitignore updates to the remote repository.</p>',
			isset( $this->options['auto_push'] ) ? "checked" : ''
			);
	}

	public function sanitize($input)
	{
		return $input;
	}

	public static function update_settings()
	{
		if(isset($_GET['settings-updated']) && $_GET['settings-updated'] == "true")
	   {

	   	  chdir(ABSPATH);
	      file_put_contents(".gitignore", $RevisrSettings['gitignore']);

	      git("add .gitignore");
	      git("commit -m 'Updated .gitignore'");

	      $options = get_option('revisr_settings');
	      if ($options['username'] != "") {
	      	git('config user.name "' . $options['username'] . '"');
	      }
	      if ($options['email'] != "") {
	      	git('config user.email "' . $options['email'] . '"');
	      }
	      if ($options['remote_url'] != "") {
	      	git('config remote.origin.url ' . $options['remote_url']);
	      }
		  if (isset($this->options['auto_push'])) {
			$errors = git_passthru("push origin {$this->branch} --quiet");
			if ($errors != "") {
				wp_redirect(get_admin_url() . "admin.php?page=revisr_settings&error=push");
			}

		  }
	      chdir($this->dir);
	   }
	}
}

