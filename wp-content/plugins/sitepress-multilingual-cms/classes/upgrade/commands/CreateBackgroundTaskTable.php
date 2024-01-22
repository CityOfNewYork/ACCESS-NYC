<?php

namespace WPML\Upgrade\Commands;

use WPML\Core\BackgroundTask\Model\BackgroundTask;
use SitePress_Setup;

class CreateBackgroundTaskTable implements \IWPML_Upgrade_Command {

	/** @var \WPML_Upgrade_Schema $schema */
	private $schema;

	/** @var bool $result */
	private $result = false;

	public function __construct( array $args ) {
		$this->schema = $args[0];
	}

	/**
	 * @return bool
	 */
	public function run() {
		$wpdb = $this->schema->get_wpdb();

		$table_name      = $wpdb->prefix . BackgroundTask::TABLE_NAME;
		$charset_collate = SitePress_Setup::get_charset_collate();

		$query = "
			CREATE TABLE IF NOT EXISTS `{$table_name}` (
				`task_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`task_type` VARCHAR(500) NOT NULL,
				`task_status` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				`starting_date` DATETIME NULL,
				`total_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`completed_count` INT UNSIGNED NOT NULL DEFAULT 0,
				`completed_ids` TEXT NULL DEFAULT NULL,
				`payload` TEXT NULL DEFAULT NULL,
				`retry_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0
			) {$charset_collate};
		";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->result = $wpdb->query( $query );

		return $this->result;
	}

	/**
	 * Runs in admin pages.
	 *
	 * @return bool
	 */
	public function run_admin() {
		return $this->run();
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_ajax() {
		return null;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_frontend() {
		return null;
	}

	/**
	 * @return bool
	 */
	public function get_results() {
		return $this->result;
	}
}
