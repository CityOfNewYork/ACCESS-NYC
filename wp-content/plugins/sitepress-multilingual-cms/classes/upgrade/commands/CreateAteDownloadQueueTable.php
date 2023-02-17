<?php

namespace WPML\TM\Upgrade\Commands;

use SitePress_Setup;

class CreateAteDownloadQueueTable implements \IWPML_Upgrade_Command {

	const TABLE_NAME = 'icl_translation_downloads';

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

		$tableName      = $wpdb->prefix . self::TABLE_NAME;
		$charsetCollate = SitePress_Setup::get_charset_collate();

		$query = "
			CREATE TABLE IF NOT EXISTS `{$tableName}` (
			  `editor_job_id` BIGINT(20) UNSIGNED NOT NULL,
			  `download_url` VARCHAR(2000) NOT NULL,
			  `lock_timestamp` INT(11) UNSIGNED NULL,
			  PRIMARY KEY (`editor_job_id`)
			) ENGINE=INNODB {$charsetCollate};
		";

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
