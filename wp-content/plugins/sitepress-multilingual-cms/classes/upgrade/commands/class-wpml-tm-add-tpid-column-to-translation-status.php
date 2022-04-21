<?php

class WPML_TM_Add_TP_ID_Column_To_Translation_Status extends WPML_Upgrade_Run_All {

	/** @var WPML_Upgrade_Schema */
	private $upgrade_schema;

	public function __construct( array $args ) {
		$this->upgrade_schema = $args[0];
	}

	/** @return bool */
	protected function run() {
		$table  = 'icl_translation_status';
		$column = 'tp_id';

		if ( $this->upgrade_schema->does_table_exist( $table ) ) {
			if ( ! $this->upgrade_schema->does_column_exist( $table, $column ) ) {
				$this->upgrade_schema->add_column( $table, $column, 'INT NULL DEFAULT NULL' );
			}
		}

		$this->result = true;

		return $this->result;
	}
}
