<?php

class WPML_TM_Add_TP_Revision_And_TS_Status_Columns_To_Translation_Status extends WPML_Upgrade_Run_All {

	/** @var WPML_Upgrade_Schema */
	private $upgrade_schema;

	public function __construct( array $args ) {
		$this->upgrade_schema = $args[0];
	}

	/** @return bool */
	protected function run() {
		$table   = 'icl_translation_status';
		$columns = array(
			'tp_revision' => 'INT NOT NULL DEFAULT 1',
			'ts_status'   => 'TEXT NULL DEFAULT NULL'
		);

		if ( $this->upgrade_schema->does_table_exist( $table ) ) {
			foreach ( $columns as $column => $definition ) {
				if ( ! $this->upgrade_schema->does_column_exist( $table, $column ) ) {
					$this->upgrade_schema->add_column( $table, $column, $definition );
				}
			}
		}

		$this->result = true;

		return $this->result;
	}
}
