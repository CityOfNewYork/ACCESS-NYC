<?php
/**
 * Abstract class to upgrade a table by adding a column to it.
 *
 * @package WPML
 */

namespace WPML\Upgrade\Commands;

/**
 * Class Add_Index_To_Table
 */
abstract class AddIndexToTable extends \WPML_Upgrade_Run_All {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	abstract protected function get_table();

	/**
	 * Get index name.
	 *
	 * @return string
	 */
	abstract protected function get_index();

	/**
	 * Get index definition.
	 *
	 * @return string
	 */
	abstract protected function get_index_definition();

	/**
	 * Upgrade schema.
	 *
	 * @var \WPML_Upgrade_Schema
	 */
	private $upgrade_schema;

	/**
	 * Add_Index_To_Table constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
		$this->upgrade_schema = $args[0];
	}

	/**
	 * Run the table upgrade.
	 *
	 * @return bool
	 */
	protected function run() {
		$this->result = false;

		if ( $this->upgrade_schema->does_table_exist( $this->get_table() ) ) {
			if ( ! $this->upgrade_schema->does_index_exist( $this->get_table(), $this->get_index() ) ) {
				$this->result = $this->upgrade_schema->add_index( $this->get_table(), $this->get_index(), $this->get_index_definition() );
			} else {
				$this->result = true;
			}
		}

		return $this->result;
	}
}
