<?php

class WPML_PO_Import_Strings {

	const NONCE_NAME = 'wpml-po-import-strings';

	private $errors;

	/** @var SitePress $sitepress */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function maybe_import_po_add_strings() {
		if ( array_key_exists( 'icl_po_upload', $_POST ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'icl_po_form' ) ) {
			add_filter( 'wpml_st_get_po_importer', array( $this, 'import_po' ) );
			return;
		}

		if ( array_key_exists( 'action', $_POST ) && 'icl_st_save_strings' === $_POST['action'] && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'add_po_strings' ) ) {
			$this->add_strings();
		}
	}

	/**
	 * @return null|WPML_PO_Import
	 */
	public function import_po() {
		if ( $_FILES[ 'icl_po_file' ][ 'size' ] === 0 ) {
			$this->errors = esc_html__( 'File upload error', 'wpml-string-translation' );
			return null;
		} else {
			$po_importer  = new WPML_PO_Import( $_FILES[ 'icl_po_file' ][ 'tmp_name' ] );
			$this->errors = $po_importer->get_errors();
			return $po_importer;
		}
	}

	/**
	 * @return string
	 */
	public function get_errors() {
		return $this->errors;
	}

	private function add_strings() {
		/** @var WPML_ST_String_Factory $wpml_st_string_factory */
		$wpml_st_string_factory = WPML\Container\make( WPML_ST_String_Factory::class );
		$strings                = json_decode( $_POST['strings_json'] );
		$source_lang            = $this->get_filtered_source_lang();

		foreach ( (array) $strings as $string ) {
			$original = WPML_Kses_Post::wp_kses_post_preserve_tags_format( $string->original );
			$context  = (string) \WPML\API\Sanitize::string( $string->context );

			$string->original = str_replace( '\n', "\n", $original );
			$name             = isset( $string->name )
				? (string) \WPML\API\Sanitize::string( $string->name ) : md5( $original );

			$string_id = icl_register_string( array(
				'domain'  => (string) \WPML\API\Sanitize::string( $_POST['icl_st_domain_name']),
				'context' => $context
			),
				$name,
				$original,
				false,
				$source_lang
			);

			if ( ! $string_id ) {
				continue;
			}

			$registered_string_lang = $wpml_st_string_factory->find_by_id( $string_id )->get_language();
			if ( $registered_string_lang !== $source_lang ) {
				// If any string already exists in different language than selected source language, exit with error.
				$source_lang_details = $this->sitepress->get_language_details( $source_lang );
				$registered_string_lang_details = $this->sitepress->get_language_details( $registered_string_lang );
				$this->errors = sprintf(
					/* translators: 1: Language name, 2: Language name, 3: Opening anchor tag, 4: Closing anchor tag. */
					esc_html__( 'You\'re trying to import strings that are already registered in %1$s. To import them as %2$s, first %3$schange the source language of existing strings%4$s using String Translation. Then, try importing them again.', 'wpml-string-translation' ),
					$registered_string_lang_details['display_name'] ?? $registered_string_lang,
					$source_lang_details['display_name'] ?? $source_lang,
					'<a target="_blank" class="external-link" href="https://wpml.org/documentation/getting-started-guide/string-translation/how-to-change-the-source-language-of-strings/?utm_source=plugin&utm_medium=gui&utm_campaign=string-translation">',
					'</a>'
				);
				break;
			}

			$this->maybe_add_translation( $string_id, $string );
		}
	}

	/**
	 * @param int|false|null $string_id
	 * @param \stdClass      $string
	 */
	private function maybe_add_translation( $string_id, $string ) {
		if ( $string_id && array_key_exists( 'icl_st_po_language', $_POST ) ) {
			if ( $string->translation !== '' ) {
				$status = ICL_TM_COMPLETE;
				if ( $string->fuzzy ) {
					$status = ICL_TM_NOT_TRANSLATED;
				}
				$translation = str_replace( '\n', "\n", wp_kses_post( $string->translation ) );

				icl_add_string_translation( $string_id, $_POST[ 'icl_st_po_language' ], $translation, $status );
				icl_update_string_status( $string_id );
			}
		}
	}

	/**
	 * Wrapper function for `filter_input()`.
	 * @return string
	 */
	protected function get_filtered_source_lang(): string {
		return ! empty( $_POST['icl_st_po_source_language'] )
			? filter_input( INPUT_POST, 'icl_st_po_source_language', FILTER_SANITIZE_FULL_SPECIAL_CHARS )
			: '';
	}
}
