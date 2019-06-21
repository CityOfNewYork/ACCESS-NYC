<?php
/**
 * WPML_ST_Upgrade_DB_Longtext_String_Value class file.
 *
 * @package wpml-string-translation
 */

/**
 * Class WPML_ST_Upgrade_DB_Longtext_String_Value
 */
class WPML_ST_Upgrade_DB_Longtext_String_Value implements IWPML_St_Upgrade_Command {
	/**
	 * WP db instance.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * WPML_ST_Upgrade_DB_Longtext_String_Value constructor.
	 *
	 * @param wpdb $wpdb WP db instance.
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Run upgrade.
	 *
	 * @return bool
	 */
	public function run() {
		$result = true;

		$table_name = $this->wpdb->prefix . 'icl_strings';
		if ( count( (array) $this->wpdb->get_results( "SHOW TABLES LIKE '{$table_name}'" ) ) ) {
			$sql = "
				ALTER TABLE {$table_name}
				MODIFY COLUMN `value` LONGTEXT NOT NULL;
			";

			$result = false !== $this->wpdb->query( $sql );
		}

		$table_name = $this->wpdb->prefix . 'icl_string_translations';
		if ( count( (array) $this->wpdb->get_results( "SHOW TABLES LIKE '{$table_name}'" ) ) ) {
			$sql = "
				ALTER TABLE {$table_name}
				MODIFY COLUMN `value` LONGTEXT NULL DEFAULT NULL,
				MODIFY COLUMN `mo_string` LONGTEXT NULL DEFAULT NULL;
			";

			$result = ( false !== $this->wpdb->query( $sql ) ) && $result;
		}

		return $result;
	}

	/**
	 * Run upgrade in ajax.
	 *
	 * @return bool
	 */
	public function run_ajax() {
		return $this->run();
	}

	/**
	 * Run upgrade on frontend.
	 *
	 * @return bool
	 */
	public function run_frontend() {
		return $this->run();
	}

	/**
	 * Get command id.
	 *
	 * @return string
	 */
	public static function get_command_id() {
		return __CLASS__;
	}
}
