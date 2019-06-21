<?php

class WPML_ST_Translations_File_Scan {

	/**
	 * @var WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory
	 */
	private $charset_filter_factory;

	/**
	 * @param WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory $charset_filter_factory
	 */
	public function __construct( WPML_ST_Translations_File_Scan_Db_Charset_Filter_Factory $charset_filter_factory ) {
		$this->charset_filter_factory = $charset_filter_factory;
	}

	/**
	 * @param string $file
	 *
	 * @return WPML_ST_Translations_File_Translation[]
	 */
	public function load_translations( $file ) {
		if ( ! file_exists( $file ) ) {
			return array();
		}

		$translations = array();
		$file_type    = pathinfo( $file, PATHINFO_EXTENSION );

		switch( $file_type ) {
			case 'mo':
				$translations_file = new WPML_ST_Translations_File_MO( $file );
				$translations      = $translations_file->get_translations();
				break;

			case 'json':
				$translations_file = new WPML_ST_Translations_File_JED( $file );
				$translations      = $translations_file->get_translations();
				break;
		}

		$unicode_characters_filter = $this->charset_filter_factory->create();
		if ( $unicode_characters_filter ) {
			$translations = $unicode_characters_filter->filter( $translations );
		}

		return $translations;
	}
}
