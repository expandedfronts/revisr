<?php

class RevisrGitTest extends WP_UnitTestCase {

	/**
	 * The main Revisr object.
	 */
	protected $revisr;

	/**
	 * Initialize the Git object.
	 */
    function setUp() {

		$this->revisr = Revisr::get_instance();
	}

	/**
	 * Restore the Git object.
	 */
	function tearDown() {
		if ( $this->revisr->git->current_branch() != 'master' ) {
			$this->revisr->git->checkout( 'master' );
		}
	}

	/**
	 * Tests for the current Git version.
	 * Expects string containing "git".
	 */
	function test_version() {
		$version = $this->revisr->git->version();
		$this->assertStringStartsWith( 'git', $version );
	}

	/**
	 * Tests the init function.
	 */
	function test_init_repo() {
		if ( ! $this->revisr->git->is_repo ) {
			$this->revisr->git->init_repo();
		}
		$this->assertEquals( true, $this->revisr->git->is_repo );
	}

	/**
	 * Tests setting the Git username.
	 */
	function test_config() {
		$this->revisr->git->set_config( 'user', 'name', 'revisr' );
		$current_user = $this->revisr->git->get_config( 'user', 'name' );
		$this->assertEquals( 'revisr', $current_user );
	}

	/**
	 * Tests the current dir with an initialized repository.
	 */
	function test_git_dir() {
		$dir = $this->revisr->git->get_git_dir();
		$this->assertFileExists( $dir );
		$this->assertFileExists( $dir . '/.git/config' );
	}

	/**
	 * Tests a commit.
	 */
	function test_commit() {
		$this->revisr->git->run( 'add', array( '-A' ) );
		$this->revisr->git->commit( 'Committed pending files' );
		$this->assertEquals( 0, $this->revisr->git->count_untracked() );
	}

	/**
	 * Tests the branches function.
	 */
	function test_branches() {
		$branches = $this->revisr->git->get_branches();
		$this->assertContains( '* ', $branches[0] );
	}

	/**
	 * Tests creating a new branch.
	 */
	function test_create_branch() {
		$this->revisr->git->create_branch( 'testbranch' );
		$this->revisr->git->create_branch( 'deletethisbranch' );
		$this->assertEquals( true, $this->revisr->git->is_branch( 'testbranch' ) );
		$this->assertEquals( true, $this->revisr->git->is_branch( 'deletethisbranch' ) );
	}

	/**
	 * Tests the is_branch function.
	 */
	function test_is_branch() {
		$real_branch = $this->revisr->git->is_branch( 'testbranch' );
		$fake_branch = $this->revisr->git->is_branch( 'fakebranch' );
		$this->assertEquals( true, $real_branch );
		$this->assertEquals( false, $fake_branch );
	}

	/**
	 * Tests checking out a branch.
	 */
	function test_checkout() {
		$this->revisr->git->checkout( 'testbranch' );
		$current_branch = $this->revisr->git->current_branch();
		$this->assertEquals( 'testbranch', $current_branch );
	}

	/**
	 * Tests deleting a branch.
	 */
	function test_delete_branch() {
		$this->revisr->git->delete_branch( 'testbranch' );
		$this->revisr->git->delete_branch( 'deletethisbranch' );
		$is_branch = $this->revisr->git->is_branch( 'deletethisbranch' );
		$this->assertEquals( false, $is_branch );
	}

	/**
	 * Tests the count_untracked() function.
	 */
	function test_count_untracked() {
		$time = time();
		fopen("sample-file_$time.txt", "w");
		$new_untracked = $this->revisr->git->count_untracked();
		$this->assertEquals( 1, $new_untracked );
	}

	/**
	 * Tests the reset functionality.
	 */
	function test_reset() {
		$this->revisr->git->reset( '--hard', 'HEAD', true );
		$after_reset  = $this->revisr->git->count_untracked();
		$this->assertEquals( 0, $after_reset );
	}

	/**
	 * Tests the Git status functionality.
	 */
	function test_status() {
		$status = $this->revisr->git->status();
		$this->assertNotEquals( false, $status );
	}

	/**
	 * Tests the current_commit() function. Expects 7 digit short SHA1 hash.
	 */
	function test_current_commit() {
		$current 	= $this->revisr->git->current_commit();
		$length 	= strlen($current);
		$this->assertEquals( 7, $length );
	}

	/**
	 * Tests the tag() function.
	 */
	function test_tag() {
		$this->revisr->git->tag( 'v1.0' );
		$tags 	= $this->revisr->git->run( 'tag', array() );
		$this->assertContains( 'v', $tags[0] );
		$this->revisr->git->tag( '-d v1.0' );
	}
}
