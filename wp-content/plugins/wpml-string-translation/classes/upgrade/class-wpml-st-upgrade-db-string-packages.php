<?php

/**
 * Class WPML_ST_Upgrade_DB_String_Packages
 */
class WPML_ST_Upgrade_DB_String_Packages implements IWPML_St_Upgrade_Command {
	private $wpdb;

	/**
	 * WPML_ST_Upgrade_DB_String_Packages constructor.
	 *
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function run() {
		$sql_get_st_package_table_name = "SHOW TABLES LIKE '{$this->wpdb->prefix}icl_string_packages'";

		$st_packages_table_exist = $this->wpdb->get_var( $sql_get_st_package_table_name ) === "{$this->wpdb->prefix}icl_string_packages";

		if ( ! $st_packages_table_exist ) {
			return false;
		}

		$sql_get_post_id_column_from_st_package = "SHOW COLUMNS FROM {$this->wpdb->prefix}icl_string_packages LIKE 'post_id'";
		$post_id_column_exists                  = $st_packages_table_exist
			? $this->wpdb->get_var( $sql_get_post_id_column_from_st_package ) === 'post_id'
			: false;

		if ( ! $post_id_column_exists ) {
			$sql = "ALTER TABLE {$this->wpdb->prefix}icl_string_packages ADD COLUMN `post_id` INTEGER";
			return (bool) $this->wpdb->query( $sql );
		}

		return true;
	}

	public function run_ajax() {
		return $this->run();
	}

	public function run_frontend() {
	}

	/**
	 * @return string
	 */
	public static function get_command_id() {
		return __CLASS__ . '_2.4.2';
	}
}
