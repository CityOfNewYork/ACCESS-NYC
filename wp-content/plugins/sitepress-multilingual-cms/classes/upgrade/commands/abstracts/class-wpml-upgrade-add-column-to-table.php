<?php
/**
 * Abstract class to upgrade a table by adding a column to it.
 *
 * @package WPML
 */

/**
 * Class WPML_Upgrade_Add_Column_To_Table
 */
abstract class WPML_Upgrade_Add_Column_To_Table implements IWPML_Upgrade_Command {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	abstract protected function get_table();

	/**
	 * Get column name.
	 *
	 * @return string
	 */
	abstract protected function get_column();

	/**
	 * Get column definition.
	 *
	 * @return string
	 */
	abstract protected function get_column_definition();

	/**
	 * Upgrade schema.
	 *
	 * @var WPML_Upgrade_Schema
	 */
	private $upgrade_schema;

	/**
	 * WPML_Upgrade_Add_Column_To_Table constructor.
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( array $args ) {
		$this->upgrade_schema = $args[0];
	}

	/**
	 * Run the table upgrade.
	 *
	 * @return bool
	 */
	private function run() {
		if ( $this->upgrade_schema->does_table_exist( $this->get_table() ) ) {
			if ( ! $this->upgrade_schema->does_column_exist( $this->get_table(), $this->get_column() ) ) {
				$this->upgrade_schema->add_column( $this->get_table(), $this->get_column(), $this->get_column_definition() );
			}
		}

		return true;
	}

	/**
	 * Run in admin.
	 *
	 * @return bool
	 */
	public function run_admin() {
		return $this->run();
	}

	/**
	 * Run in ajax.
	 *
	 * @return bool
	 */
	public function run_ajax() {
		return $this->run();
	}

	/**
	 * Run in frontend.
	 *
	 * @return bool
	 */
	public function run_frontend() {
		return $this->run();
	}

	/**
	 * Get upgrade results.
	 *
	 * @return bool
	 */
	public function get_results() {
		return true;
	}
}
