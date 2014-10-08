<?php

class RevisrGitTest extends WP_UnitTestCase {

	/**
	 * The Git object.
	 */
	protected $git;

	/**
	 * Initialize the Git object.
	 */
    function setUp() {
		$this->git = new Revisr_Git();
	}

	/**
	 * Tests for the current Git version.
	 * Expects string containing "git".
	 */
	function test_version() {
		$version = $this->git->version();
		$this->assertStringStartsWith( 'git', $version );
	}

	/**
	 * Tests the current dir with an initialized repository.
	 */
	function test_current_dir() {
		$dir = $this->git->current_dir();
		$this->assertFileExists( $dir );
		$this->assertFileExists( $dir . '/.git/config' );
	}

	/**
	 * Tests setting the Git username.
	 */
	function test_config_user_name() {
		$this->git->config_user_name( 'revisr' );
		$current_user = $this->git->run( 'config user.name' );
		$this->assertEquals( 'revisr', $current_user[0] );
	}

	/**
	 * Tests setting the Git email address.
	 */
	function test_config_user_email() {
		$this->git->config_user_email( 'support@expandedfronts.com' );
		$current_email = $this->git->run( 'config user.email' );
		$this->assertEquals( 'support@expandedfronts.com', $current_email[0] );
	}

	/**
	 * Tests a commit.
	 */
	function test_commit() {
		$this->git->run( 'add -A' );
		$this->git->commit( 'Committed pending files' );
		$this->assertEquals( 0, $this->git->count_untracked() );
	}

	/**
	 * Tests the branches function.
	 */
	function test_branches() {
		$branches = $this->git->branches();
		$this->assertContains( 'master', $branches[0] );
	}

	/**
	 * Tests the is_branch function.
	 */
	function test_is_branch() {
		$real_branch = $this->git->is_branch( 'master' );
		$fake_branch = $this->git->is_branch( 'fakebranch' );
		$this->assertEquals( true, $real_branch );
		$this->assertEquals( false, $fake_branch );
	}

	/**
	 * Tests creating a new branch.
	 */
	function test_create_branch() {
		$this->git->create_branch( 'testbranch' );
		$this->git->create_branch( 'deletethisbranch' );
		$this->assertEquals( true, $this->git->is_branch( 'testbranch' ) );
		$this->assertEquals( true, $this->git->is_branch( 'deletethisbranch' ) );
	}

	/**
	 * Tests checking out a branch.
	 */
	function test_checkout() {
		$this->git->checkout( 'testbranch' );
		$this->assertEquals( 'testbranch', $this->git->branch );
	}

	/**
	 * Tests deleting a branch.
	 */
	function test_delete_branch() {
		$this->git->delete_branch( 'deletethisbranch' );
		$is_branch = $this->git->is_branch( 'deletethisbranch' );
		$this->assertEquals( false, $is_branch );
	}

	/**
	 * Tests the count_untracked() function.
	 */
	function test_count_untracked() {
		$myfile = fopen("sample-file2.txt", "w");
		$new_untracked = $this->git->count_untracked();
		$this->assertEquals( 1, $new_untracked );
	}

	/**
	 * Tests the reset functionality.
	 */
	function test_reset() {
		$this->git->reset( '--hard', 'HEAD', true );
		$after_reset  = $this->git->count_untracked();
		$this->assertEquals( 0, $after_reset );
	}

	/**
	 * Tests the Git status functionality.
	 */
	function test_status() {
		$status = $this->git->status();
		$this->assertNotEquals( false, $status );
	}
}
