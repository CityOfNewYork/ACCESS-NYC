<?php
/**
 * Upgrade 'icl_translation_status' table by adding 'uuid' column.
 *
 * @package WPML
 */

/**
 * Class WPML_Add_UUID_Column_To_Translation_Status
 */
class WPML_Add_UUID_Column_To_Translation_Status extends WPML_Upgrade_Add_Column_To_Table {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected function get_table() {
		return 'icl_translation_status';
	}

	/**
	 * Get column name.
	 *
	 * @return string
	 */
	protected function get_column() {
		return 'uuid';
	}

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	protected function get_column_definition() {
		return 'VARCHAR(36) NULL';
	}
}
