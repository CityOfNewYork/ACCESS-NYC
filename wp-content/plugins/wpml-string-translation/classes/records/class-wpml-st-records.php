<?php

class WPML_ST_Records {

	/** @var wpdb $wpdb */
	public $wpdb;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/** @retur wpdb */
	public function get_wpdb() {
		return $this->wpdb;
	}

	/**
	 * @param int $string_id
	 *
	 * @return WPML_ST_ICL_Strings
	 */
	public function icl_strings_by_string_id( $string_id ) {

		return new WPML_ST_ICL_Strings( $this->wpdb, $string_id );
	}

	/**
	 * @param int    $string_id
	 * @param string $language_code
	 *
	 * @return WPML_ST_ICL_String_Translations
	 */
	public function icl_string_translations_by_string_id_and_language( $string_id, $language_code ) {

		return new WPML_ST_ICL_String_Translations( $this->wpdb, $string_id, $language_code );
	}
}
