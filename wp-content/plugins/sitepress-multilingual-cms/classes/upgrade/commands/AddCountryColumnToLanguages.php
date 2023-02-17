<?php


namespace WPML\Upgrade\Commands;


class AddCountryColumnToLanguages extends \WPML_Upgrade_Add_Column_To_Table {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	protected function get_table() {
		return 'icl_languages';
	}

	/**
	 * Get column name.
	 *
	 * @return string
	 */
	protected function get_column() {
		return 'country';
	}

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	protected function get_column_definition() {
		return 'VARCHAR(10) NULL DEFAULT NULL';
	}

}