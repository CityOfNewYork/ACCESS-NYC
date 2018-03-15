<?php

class WPML_ST_MO_Scan_Cached_Charset_Validation implements WPML_ST_MO_Scan_Charset_Validation {

	const CACHE_OPTION = 'wpml-charset-validation';

	/** @var WPML_ST_MO_Scan_Charset_Validation */
	private $validator;

	/**
	 * @param WPML_ST_MO_Scan_Charset_Validation $validator
	 */
	public function __construct( WPML_ST_MO_Scan_Charset_Validation $validator ) {
		$this->validator = $validator;
	}

	public function is_valid() {
		$option = get_option( self::CACHE_OPTION );
		if ( false !== $option ) {
			return (bool) $option;
		}

		$result = $this->validator->is_valid();
		update_option( self::CACHE_OPTION, $result ? 1 : 0, true );

		return $result;
	}
}
