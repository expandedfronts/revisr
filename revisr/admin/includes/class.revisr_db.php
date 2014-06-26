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
	public $current_dir;
	public $upload_dir;
	public $sql_file;

	public function __construct()
	{
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function backup()
	{
		exec("mysqldump -u " . DB_USER . " -p" . DB_PASSWORD . " " . DB_NAME . " >revisr_db_backup.sql");
	}

	public function restore()
	{
		exec("mysql -u " . DB_USER . " -p" . DB_PASSWORD . " " . DB_NAME . " < revisr_db_backup.sql");
	}

	public function drop_tables()
	{
		$tables = $this->wpdb->get_results('SHOW TABLES', ARRAY_A);
		foreach ($tables as $table) {
			$table_name = $table["Tables_in_" . DB_NAME];
			$this->wpdb->query("DROP TABLE $table_name");
		}
	}
}