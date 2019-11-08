<?php
/**
 * Upgrade 'icl_translate_job' table by adding 'editor' column.
 *
 * @package WPML
 */

/**
 * Class WPML_Upgrade_Add_Editor_Column_To_Icl_Translate_Job
 */
class WPML_Upgrade_Add_Editor_Column_To_Icl_Translate_Job extends WPML_Upgrade_Add_Column_To_Table {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected function get_table() {
		return 'icl_translate_job';
	}

	/**
	 * Get column name.
	 *
	 * @return string
	 */
	protected function get_column() {
		return 'editor';
	}

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	protected function get_column_definition() {
		return 'VARCHAR(16) NULL';
	}
}
