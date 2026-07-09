<?php

use WPML\ST\TranslationFile\StringEntity;

class WPML_ST_JED_File_Builder extends WPML\ST\TranslationFile\Builder {

	/** @var string $decoded_eot */
	private $decoded_eot;

	public function __construct() {
		$this->decoded_eot = json_decode( WPML_ST_Translations_File_JED::DECODED_EOT_CHAR );
	}

	/**
	 * @param StringEntity[] $strings
	 * @return string
	 */
	public function get_content( array $strings ) {
		$data = new stdClass();

		$data->{'translation-revision-date'} = $this->get_date();
		$data->generator                     = 'WPML String Translation ' . WPML_ST_VERSION;
		$data->domain                        = 'messages';
		$data->locale_data                   = new stdClass();
		$data->locale_data->messages         = new stdClass();

		$data->locale_data->messages->{WPML_ST_Translations_File_JED::EMPTY_PROPERTY_NAME} = (object) array(
			'domain'       => 'messages',
			'plural-forms' => $this->plural_form,
			'lang'         => $this->language,
		);

		foreach ( $strings as $string ) {
			$original                                 = $this->get_original_with_context( $string );
			$data->locale_data->messages->{$original} = $string->get_translations();
		}

		return $this->encode_jed_content( $data );
	}

	private function get_original_with_context( StringEntity $string ) {
		if ( $string->get_context() ) {
			return $string->get_context() . $this->decoded_eot . $string->get_original();
		}

		return $string->get_original();
	}

	/**
	 * We will take all translations from the custom WPML file,
	 * and add all the stings translations from the native file
	 * that are missing the custom WPML file.
	 *
	 * @param string $native_filepath
	 * @param string $wpml_filepath
	 *
	 * @return string The JED file content.
	 */
	public function merge_files( $native_filepath, $wpml_filepath ) {
		$original_contents = file_get_contents( $native_filepath );
		$wpml_contents     = file_get_contents( $wpml_filepath );

		if ( ! $original_contents || ! $wpml_contents ) {
			return null;
		}

		$native_data = json_decode( $original_contents, true );
		$wpml_data   = json_decode( $wpml_contents, true );

		if (
			! isset( $native_data['locale_data']['messages'] )
			|| ! isset( $wpml_data['locale_data']['messages'] )
			|| ! is_array( $native_data['locale_data']['messages'] )
			|| ! is_array( $wpml_data['locale_data']['messages'] )
		) {
			return null;
		}

		$native_strings = (array) $native_data['locale_data']['messages'];
		$wpml_strings   = (array) $wpml_data['locale_data']['messages'];

		foreach ( $native_strings as $original => $translations ) {
			if ( ! isset( $wpml_strings[ $original ] ) ) {
				$wpml_strings[ $original ] = $translations;
			}
		}

		$wpml_data['locale_data']['messages']   = $wpml_strings;
		$wpml_data['translation-revision-date'] = $this->get_date();

		return $this->encode_jed_content( $wpml_data );
	}

	/**
	 * @return string
	 */
	private function get_date() {
		return (string) date( 'Y-m-d H:i:sO' );
	}

	/**
	 * @param array|stdClass $data
	 *
	 * @return string
	 */
	private function encode_jed_content( $data ) {
		$jed_content = (string) wp_json_encode( $data );

		return (string) preg_replace( '/"' . WPML_ST_Translations_File_JED::EMPTY_PROPERTY_NAME . '"/', '""', $jed_content, 1 );
	}
}
