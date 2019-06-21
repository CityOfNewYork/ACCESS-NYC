<?php

class WPML_ST_Translations_File_MO implements IWPML_ST_Translations_File {

	/** @var string $filepath */
	private $filepath;

	/**
	 * @param string $filepath
	 */
	public function __construct( $filepath ) {
		$this->filepath = $filepath;
	}

	/**
	 * @return WPML_ST_Translations_File_Translation[]
	 */
	public function get_translations() {
		$translations = array();
		$mo           = new MO();
		$pomo_reader  = new POMO_CachedFileReader( $this->filepath );

		$mo->import_from_reader( $pomo_reader );

		foreach ( $mo->entries as $str => $v ) {
			$str            = str_replace( "\n", '\n', $v->singular );
			$translations[] = new WPML_ST_Translations_File_Translation( $str, $v->translations[0], $v->context );

			if ( $v->is_plural ) {
				$str            = str_replace( "\n", '\n', $v->plural );
				$translation    = ! empty( $v->translations[1] ) ? $v->translations[1] : $v->translations[0];
				$translations[] = new WPML_ST_Translations_File_Translation( $str, $translation, $v->context );
			}
		}

		return $translations;
	}
}
