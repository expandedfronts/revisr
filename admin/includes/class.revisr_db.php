<?php
/**
 * revisr_db.php
 *
 * Performs database backup and restore operations.
 *
 * @package   Revisr
 * @license   GPLv3
 * @link      https://revisr.io
 * @copyright 2014 Expanded Fronts
 */

class RevisrDB
{
	public $wpdb;
	private $sql_file;
	private $options;

	public function __construct()
	{
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->sql_file = "revisr_db_backup.sql";
		$this->options = get_option('revisr_settings');

		if (DB_PASSWORD != '') {
			$this->conn = "-u " . DB_USER . " -p" . DB_PASSWORD . " " . DB_NAME . " --host " . DB_HOST;
		}
		else {
			$this->conn = "-u " . DB_USER . " " . DB_NAME . " --host " . DB_HOST;
		}

	}

	public function backup()
	{
		if (isset($this->options['mysql_path'])) {
			$path = $this->options['mysql_path'];
			exec("{$path}mysqldump {$this->conn} > {$this->sql_file}");
		}
		else {
			exec("mysqldump {$this->conn} > {$this->sql_file}");
		}
	}

	public function restore()
	{
		if (!function_exists('exec')) {
			wp_die("<p>It appears you don't have the PHP exec() function enabled. This is required to revert the database. Check with your hosting provider or enable this in your PHP configuration.</p>");
		}
		if (!file_exists($this->sql_file) || filesize($this->sql_file) < 1000) {
			wp_die("<p>Failed to revert the database: The backup file does not exist or has been corrupted.</p>");
		}

		clearstatcache();

		if (isset($this->options['mysql_path'])) {
			$path = $this->options['mysql_path'];
			exec("{$path}mysql {$this->conn} < {$this->sql_file}");
		}
		else {
			exec("mysql {$this->conn} < {$this->sql_file}");
		}
	}
}