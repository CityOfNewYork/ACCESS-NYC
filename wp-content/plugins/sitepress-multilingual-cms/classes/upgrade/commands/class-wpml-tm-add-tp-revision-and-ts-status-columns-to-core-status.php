<?php

class WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Core_Status implements IWPML_Upgrade_Command {

	/** @var WPML_Upgrade_Schema */
	private $upgrade_schema;

	public function __construct( array $args ) {
		$this->upgrade_schema = $args[0];
	}

	/** @return bool */
	private function run() {
		$table   = 'icl_core_status';
		$columns = array(
			'tp_revision' => 'INT NOT NULL DEFAULT 1',
			'ts_status'   => 'TEXT NULL DEFAULT NULL',
		);

		if ( $this->upgrade_schema->does_table_exist( $table ) ) {
			foreach ( $columns as $column => $definition ) {
				if ( ! $this->upgrade_schema->does_column_exist( $table, $column ) ) {
					$this->upgrade_schema->add_column( $table, $column, $definition );
				}
			}
		}

		return true;
	}

	public function run_admin() {
		return $this->run();
	}

	public function run_ajax() {
		return null;
	}

	public function run_frontend() {
		return null;
	}

	/** @return bool */
	public function get_results() {
		return null;
	}
}
