<?php
/**
 * Upgrade 'icl_strings' table by adding 'word_count' column.
 *
 * @package WPML
 */

/**
 * Class WPML_Upgrade_Add_Word_Count_Column_To_Strings
 */
class WPML_Upgrade_Add_Word_Count_Column_To_Strings extends WPML_Upgrade_Add_Column_To_Table {

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
		return 'word_count';
	}

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	protected function get_column_definition() {
		return 'int unsigned NULL';
	}
}
