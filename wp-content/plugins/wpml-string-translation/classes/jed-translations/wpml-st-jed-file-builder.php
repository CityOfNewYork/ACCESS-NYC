<?php

class WPML_ST_JED_File_Builder {

	/** @var WPML_ST_JED_String[] $strings */
	private $strings = array();

	/** @var string $language */
	private $language;

	/** @var string $plural_form */
	private $plural_form = 'nplurals=2; plural=n != 1;';

	/** @var string $decoded_eot */
	private $decoded_eot;

	public function __construct() {
		$this->decoded_eot = json_decode( WPML_ST_Translations_File_JED::DECODED_EOT_CHAR );
	}

	/**
	 * @param WPML_ST_JED_String[] $strings
	 *
	 * @return $this
	 */
	public function set_strings( array $strings ) {
		$this->strings = $strings;
		return $this;
	}

	/**
	 * @param string $language
	 *
	 * @return $this
	 */
	public function set_language( $language ) {
		$this->language = $language;
		return $this;
	}

	/**
	 * @param string $plural_form
	 *
	 * @return $this
	 */
	public function set_plural_form( $plural_form ) {
		$this->plural_form = $plural_form;
		return $this;
	}

	/** @return string */
	public function get_content() {
		$data = new stdClass();

		$data->{'translation-revision-date'} = date( 'Y-m-d H:i:sO' );
		$data->generator = 'WPML String Translation ' . WPML_ST_VERSION;
		$data->domain = 'messages';
		$data->locale_data = new stdClass();
		$data->locale_data->messages = new stdClass();

		$data->locale_data->messages->{WPML_ST_Translations_File_JED::EMPTY_PROPERTY_NAME} = (object) array(
			'domain'       => 'messages',
			'plural-forms' => $this->plural_form,
			'lang'         => $this->language,
		);

		foreach ( $this->strings as $string ) {
			$original                                 = $this->get_original_with_context( $string );
			$data->locale_data->messages->{$original} = $string->get_translations();
		}

		$jed_content = wp_json_encode( $data );

		return preg_replace( '/"' . WPML_ST_Translations_File_JED::EMPTY_PROPERTY_NAME . '"/', '""', $jed_content, 1 );
	}

	private function get_original_with_context( WPML_ST_JED_String $string ) {
		if ( $string->get_context() ) {
			return $string->get_context() . $this->decoded_eot . $string->get_original();
		}

		return $string->get_original();
	}
}
