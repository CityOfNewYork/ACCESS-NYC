<?php
/**
 * Upgrade 'icl_strings' table by adding 'wrap' column.
 *
 * @package WPML
 */

/**
 * Class WPML_Upgrade_Add_Wrap_Column_To_Strings
 */
class WPML_Upgrade_Add_Wrap_Column_To_Strings extends WPML_Upgrade_Add_Column_To_Table {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected function get_table() {
		return 'icl_strings';
	}

	/**
	 * Get column name.
	 *
	 * @return string
	 */
	protected function get_column() {
		return 'wrap_tag';
	}

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	protected function get_column_definition() {
		return 'VARCHAR( 16 ) NOT NULL AFTER `location`';
	}
}
