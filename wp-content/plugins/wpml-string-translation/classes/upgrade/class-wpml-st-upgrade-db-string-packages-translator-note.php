<?php

class WPML_ST_Upgrade_DB_String_Packages_Translator_Note implements IWPML_St_Upgrade_Command {

	/** @var WPML_Upgrade_Schema $upgrade_schema */
	private $upgrade_schema;

	public function __construct( WPML_Upgrade_Schema $upgrade_schema ) {
		$this->upgrade_schema = $upgrade_schema;
	}

	public function run() {
		$table  = 'icl_string_packages';
		$column = 'translator_note';

		if ( ! $this->upgrade_schema->does_table_exist( $table ) ) {
			return false;
		}

		if ( ! $this->upgrade_schema->does_column_exist( $table, $column ) ) {
			return (bool) $this->upgrade_schema->add_column( $table, $column, 'LONGTEXT NULL DEFAULT NULL' );
		}

		return true;
	}

	public function run_ajax() {
		return $this->run();
	}

	public function run_frontend() {
		return $this->run();
	}

	/**
	 * @return string
	 */
	public static function get_command_id() {
		return __CLASS__;
	}
}
