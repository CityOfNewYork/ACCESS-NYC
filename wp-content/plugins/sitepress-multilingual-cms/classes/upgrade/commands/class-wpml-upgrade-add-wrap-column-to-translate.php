<?php
/**
 * Upgrade 'icl_translate' table by adding 'field_wrap_tag' column.
 *
 * @package WPML
 */

/**
 * Class WPML_Upgrade_Add_Wrap_Column_To_Translate
 */
class WPML_Upgrade_Add_Wrap_Column_To_Translate extends WPML_Upgrade_Add_Column_To_Table {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected function get_table() {
		return 'icl_translate';
	}

	/**
	 * Get column name.
	 *
	 * @return string
	 */
	protected function get_column() {
		return 'field_wrap_tag';
	}

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	protected function get_column_definition() {
		return 'VARCHAR( 16 ) NOT NULL AFTER `field_type`';
	}
}
