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
	 * Restore the Git object.
	 */
	function tearDown() {
		if ( $this->git->current_branch() != 'master' ) {
			$this->git->checkout( 'master' );
		}
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
	 * Tests the init function.
	 */
	function test_init_repo() {
		if ( ! $this->git->is_repo ) {
			$this->git->init_repo();
		}
		$this->assertEquals( true, $this->git->is_repo );
	}

	/**
	 * Tests setting the Git username.
	 */
	function test_config() {
		$this->git->set_config( 'user', 'name', 'revisr' );
		$current_user = $this->git->get_config( 'user', 'name' );
		$this->assertEquals( 'revisr', $current_user );
	}

	/**
	 * Tests the current dir with an initialized repository.
	 */
	function test_git_dir() {
		$dir = $this->git->get_git_dir();
		$this->assertFileExists( $dir );
		$this->assertFileExists( $dir . '/.git/config' );
	}

	/**
	 * Tests a commit.
	 */
	function test_commit() {
		$this->git->run( 'add', array( '-A' ) );
		$this->git->commit( 'Committed pending files' );
		$this->assertEquals( 0, $this->git->count_untracked() );
	}

	/**
	 * Tests the branches function.
	 */
	function test_branches() {
		$branches = $this->git->get_branches();
		$this->assertContains( '* ', $branches[0] );
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
	 * Tests the is_branch function.
	 */
	function test_is_branch() {
		$real_branch = $this->git->is_branch( 'testbranch' );
		$fake_branch = $this->git->is_branch( 'fakebranch' );
		$this->assertEquals( true, $real_branch );
		$this->assertEquals( false, $fake_branch );
	}

	/**
	 * Tests checking out a branch.
	 */
	function test_checkout() {
		$this->git->checkout( 'testbranch' );
		$current_branch = $this->git->current_branch();
		$this->assertEquals( 'testbranch', $current_branch );
	}

	/**
	 * Tests deleting a branch.
	 */
	function test_delete_branch() {
		$this->git->delete_branch( 'testbranch' );
		$this->git->delete_branch( 'deletethisbranch' );
		$is_branch = $this->git->is_branch( 'deletethisbranch' );
		$this->assertEquals( false, $is_branch );
	}

	/**
	 * Tests the count_untracked() function.
	 */
	function test_count_untracked() {
		fopen("sample-file_2.txt", "w");
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

	/**
	 * Tests the current_commit() function. Expects 7 digit short SHA1 hash.
	 */
	function test_current_commit() {
		$current 	= $this->git->current_commit();
		$length 	= strlen($current);
		$this->assertEquals( 7, $length );
	}

	/**
	 * Tests the tag() function.
	 */
	function test_tag() {
		$this->git->tag( 'v1.0' );
		$tags 	= $this->git->run( 'tag', array() );
		$this->assertContains( 'v', $tags[0] );
		$this->git->tag( '-d v1.0' );
	}
}
