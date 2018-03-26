<?php

class WPML_ST_Taxonomy_Labels_Translation implements IWPML_Action {

	const GENERAL_CONTEXT   = 'taxonomy general name';
	const SINGULAR_CONTEXT  = 'taxonomy singular name';

	const LEGACY_GENERAL_NAME_PREFIX  = 'taxonomy general name: ';
	const LEGACY_SINGULAR_NAME_PREFIX = 'taxonomy singular name: ';
	const LEGACY_STRING_DOMAIN        = 'WordPress';

	/** @var WPML_ST_String_Factory $string_factory */
	private $string_factory;

	/** @var array $active_languages */
	private $active_languages;

	private $strings_translated_with_gettext_context = array();

	public function __construct( WPML_ST_String_Factory $string_factory, array $active_languages ) {
		$this->string_factory   = $string_factory;
		$this->active_languages = $active_languages;
	}

	public function add_hooks() {
		add_filter( 'gettext_with_context', array( $this, 'block_translation_and_init_strings' ), PHP_INT_MAX, 4 );
		add_filter( 'wpml_label_translation_data', array( $this, 'get_label_translations' ), 10, 2 );
		add_action( 'wp_ajax_wpml_tt_save_labels_translation', array( $this, 'save_label_translations' ) );
	}

	/**
	 * @param string $translation
	 * @param string $text
	 * @param string $gettext_context
	 * @param string $domain
	 *
	 * @return mixed
	 */
	public function block_translation_and_init_strings( $translation, $text, $gettext_context, $domain ) {
		if ( self::GENERAL_CONTEXT === $gettext_context || self::SINGULAR_CONTEXT === $gettext_context ) {
			$this->find_or_create_string( $text, $gettext_context, $domain );
			$this->add_to_strings_translated_with_gettext_context( $text, $domain );

			// We need to return the original string here so the rest of
			// the label translation UI works.
			return $text;
		}

		return $translation;
	}

	/**
	 * @param string $text
	 * @param string $domain
	 */
	private function add_to_strings_translated_with_gettext_context( $text, $domain ) {
		if ( ! in_array( $text, $this->strings_translated_with_gettext_context ) ) {
			$this->strings_translated_with_gettext_context[ $text ] = $domain;
		}
	}

	/**
	 * @param string       $text
	 * @param string       $gettext_context
	 * @param string       $domain
	 * @param false|string $name
	 *
	 * @return int
	 */
	private function find_or_create_string( $text, $gettext_context = '', $domain = '', $name = false ) {
		$context = array(
			'domain'  => $domain,
			'context' => $gettext_context,
		);

		$string_id = $this->string_factory->get_string_id( $text, $context, $name );

		if ( ! $string_id ) {
			$string_id = icl_register_string( $context, $name, $text );
		}

		return $string_id;
	}

	/**
	 * @param        $false
	 * @param string $taxonomy
	 *
	 * @return array|bool
	 */
	public function get_label_translations( $false, $taxonomy ) {
		list( $general_string, $singular_string ) = $this->get_taxonomy_strings( $taxonomy );

		$data = null;

		if ( $general_string && $singular_string ) {
			$data = $this->build_label_array( $general_string, $singular_string );
		}

		return $data;
	}

	/**
	 * @param string $taxonomy_name
	 *
	 * @return WPML_ST_String[]
	 */
	private function get_taxonomy_strings( $taxonomy_name ) {
		$taxonomy = get_taxonomy( $taxonomy_name );

		if ( $taxonomy && isset( $taxonomy->label ) && isset( $taxonomy->labels->singular_name ) ) {
			$general_string  = $this->get_string( $taxonomy->label, 'general' );
			$singular_string = $this->get_string( $taxonomy->labels->singular_name, 'singular' );

			return array( $general_string, $singular_string );
		}

		return array( null, null );
	}

	/**
	 * @param WPML_ST_String $general
	 * @param WPML_ST_String $singular
	 *
	 * @return array
	 */
	private function build_label_array( WPML_ST_String $general, WPML_ST_String $singular ) {
		$source_lang = $general->get_language();

		$general_translations  = $this->get_translations( $general );
		$singular_translations = $this->get_translations( $singular );

		$data = array(
			'st_default_lang' => $source_lang,
		);

		foreach ( array_keys( $this->active_languages ) as $lang ) {
			if ( $lang === $source_lang ) {
				continue;
			}

			$data[ $lang ]['general']  = $this->get_translation_value( $lang, $general_translations );
			$data[ $lang ]['singular'] = $this->get_translation_value( $lang, $singular_translations );

			$data[ $lang ] = array_filter( $data[ $lang ] );
		}

		$data[ $source_lang ] = array(
			'general'  => $general->get_value(),
			'singular' => $singular->get_value(),
			'original' => true
		);

		return $data;
	}

	/**
	 * @param string $value
	 * @param string $general_or_singular
	 *
	 * @return WPML_ST_String|null
	 */
	private function get_string( $value, $general_or_singular ) {
		$string    = $this->get_string_details( $value, $general_or_singular );
		$string_id = $this->find_or_create_string( $value, $string['context'], $string['domain'], $string['name'] );

		if ( $string_id ) {
			return $this->string_factory->find_by_id( $string_id );
		}

		return null;
	}

	/**
	 * @param string $value
	 * @param string $general_or_singular
	 *
	 * @return array
	 */
	private function get_string_details( $value, $general_or_singular ) {
		$string_meta = array(
			'context' => '',
			'domain'  => '',
			'name'    => false,
		);

		if ( $this->is_string_translated_with_gettext_context( $value ) ) {
			$string_meta['domain'] = $this->get_domain_for_taxonomy( $value );
			if ( 'general' === $general_or_singular ) {
				$string_meta['context'] = self::GENERAL_CONTEXT;
			} else {
				$string_meta['context'] = self::SINGULAR_CONTEXT;
			}
		} else {
			$string_meta['domain'] = self::LEGACY_STRING_DOMAIN;

			if ( 'general' === $general_or_singular ) {
				$string_meta['name'] = self::LEGACY_GENERAL_NAME_PREFIX . $value;
			} else {
				$string_meta['name'] = self::LEGACY_SINGULAR_NAME_PREFIX . $value;
			}
		}

		return $string_meta;
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	private function is_string_translated_with_gettext_context( $value ) {
		return array_key_exists( $value, $this->strings_translated_with_gettext_context );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	private function get_domain_for_taxonomy( $value ) {
		return $this->strings_translated_with_gettext_context[ $value ];
	}

	/**
	 * @param WPML_ST_String $string
	 *
	 * @return array
	 */
	private function get_translations( WPML_ST_String $string ) {
		$translations = array();

		foreach ( $string->get_translations() as $translation ) {
			$translations[ $translation->language ] = $translation;
		}

		return $translations;
	}

	/**
	 * @param string $lang
	 * @param array  $translations
	 *
	 * @return string|null
	 */
	private function get_translation_value( $lang, array $translations ) {
		$value = null;

		if ( isset( $translations[ $lang ] ) ) {
			if ( $translations[ $lang ]->value ) {
				$value = $translations[ $lang ]->value;
			} elseif ( $translations[ $lang ]->mo_string ) {
				$value = $translations[ $lang ]->mo_string;
			}
		}

		return $value;
	}

	public function save_label_translations() {
		if ( ! wpml_is_action_authenticated( 'wpml_tt_save_labels_translation' ) ) {
			wp_send_json_error( 'Wrong Nonce' );
			return;
		}

		$general_translation  = isset( $_POST[ 'plural' ] ) ? sanitize_text_field( $_POST[ 'plural' ] ) : false;
		$singular_translation = isset( $_POST[ 'singular' ] ) ? sanitize_text_field( $_POST[ 'singular' ] ) : false;
		$taxonomy_name        = isset( $_POST[ 'taxonomy' ] ) ? sanitize_text_field( $_POST[ 'taxonomy' ] ) : false;
		$language             = isset( $_POST[ 'taxonomy_language_code' ] )
			? sanitize_text_field( $_POST[ 'taxonomy_language_code' ] ): false;

		if ( $general_translation && $singular_translation && $taxonomy_name && $language ) {
			list( $general, $singular ) = $this->get_taxonomy_strings( $taxonomy_name );

			if ( $general && $singular ) {
				$general->set_translation( $language, $general_translation, ICL_STRING_TRANSLATION_COMPLETE );
				$singular->set_translation( $language, $singular_translation, ICL_STRING_TRANSLATION_COMPLETE );

				$result = array(
					'general'  => $general_translation,
					'singular' => $singular_translation,
					'lang'     => $language
				);

				wp_send_json_success( $result );
				return;
			}
		}

		wp_send_json_error();
	}
}