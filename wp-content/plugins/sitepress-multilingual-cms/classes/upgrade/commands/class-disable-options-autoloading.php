<?php

namespace WPML\Upgrade\Command;

class DisableOptionsAutoloading implements \IWPML_Upgrade_Command {

	/** @var bool */
	private $results;

	public function run() {
		global $wpdb;

		$do_not_autoload_options = [
			'_icl_cache',
			'wp_installer_settings',
			'wpml_translation_services',
			'_wpml_batch_report',
		];

		$where = 'WHERE option_name IN (' . wpml_prepare_in( $do_not_autoload_options ) . ") AND autoload = 'yes'";

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query_result = $wpdb->query( "UPDATE {$wpdb->prefix}options SET autoload = 'no' {$where}" );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

		$this->results = false !== $query_result;

		return $this->results;
	}

	public function run_admin() {
		return $this->run();
	}

	public function run_ajax() {
		return null;
	}

	public function run_frontend() {
		return null;
	}

	public function get_results() {
		return $this->results;
	}
}
