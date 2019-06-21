<?php

class WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory {

	/** @var wpdb $wpdb */
	private $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function create() {
		$charset_validator = new WPML_ST_Translations_File_Scan_Db_Charset_Validation( $this->wpdb, new WPML_ST_Translations_File_Scan_Db_Table_List( $this->wpdb ) );

		return $charset_validator->is_valid() ? null : new WPML_ST_Translations_File_Unicode_Characters_Filter();
	}

}
