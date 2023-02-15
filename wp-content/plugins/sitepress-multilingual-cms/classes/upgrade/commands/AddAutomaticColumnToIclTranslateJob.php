<?php


namespace WPML\Upgrade\Commands;


class AddAutomaticColumnToIclTranslateJob extends \WPML_Upgrade_Add_Column_To_Table {
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
		return 'automatic';
	}

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	protected function get_column_definition() {
		return 'TINYINT UNSIGNED NOT NULL DEFAULT 0';
	}
}