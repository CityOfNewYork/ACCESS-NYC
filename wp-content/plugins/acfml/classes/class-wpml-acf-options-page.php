<?php

use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;

class WPML_ACF_Options_Page {
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var WPML_ACF_Worker
	 */
	private $acfWorker;

	/**
	 * @var string
	 */
	const ORIGINAL_ID = 'options';

	/**
	 * @var string
	 */
	const TRANSLATED_ID_FORMAT = 'options_%s';

	/**
	 * WPML_ACF_Options_Page constructor.
	 *
	 * @param SitePress       $sitepress
	 * @param WPML_ACF_Worker $acfWorker
	 */
	public function __construct( SitePress $sitepress, WPML_ACF_Worker $acfWorker ) {
		$this->sitepress = $sitepress;
		$this->acfWorker = $acfWorker;
	}

	/**
	 * Adds filters for displaying options page value and updating this value.
	 */
	public function register_hooks() {
		add_filter( 'acf/pre_render_fields', [ $this, 'fields_on_translated_options_page' ], 10, 2 );
		add_filter( 'acf/update_value', [ $this, 'overwrite_option_value' ], 10, 4 );
		add_filter( 'acf/validate_post_id', [ $this, 'append_language_code_for_option_pages' ] );
	}

	/**
	 * @return bool Tells if currently displayed page is ACF options page within wp-admin.
	 */
	public function is_acf_options_page() {
		return is_admin()
			&& function_exists( 'acf_get_options_page' )
			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			&& acf_get_options_page( sanitize_text_field( wp_unslash( Obj::prop( 'page', $_REQUEST ) ) ) );
	}

	/**
	 * Updates fields values on display at ACF options page.
	 *
	 * @param array $fields  All fields to be displayed on AF options page.
	 * @param mixed $post_id Post id or string identifying current option page.
	 *
	 * @return array
	 */
	public function fields_on_translated_options_page( $fields, $post_id = 0 ) {
		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = $this->get_field_options( $field, $post_id );
		}
		return $fields;
	}

	/**
	 * @param array $field
	 * @param mixed $post_id
	 *
	 * @return array
	 */
	private function get_field_options( array $field, $post_id ) {
		if ( isset( $field['wpml_cf_preferences'] ) ) {
			switch ( $field['wpml_cf_preferences'] ) {
				case WPML_COPY_CUSTOM_FIELD:
					if ( $this->is_field_on_translated_options_page( $post_id ) ) {
						if ( $this->is_repeater_field( $field ) ) {
							$field['value'] = acf_get_value( self::ORIGINAL_ID, $field );
							$field          = $this->updateTranslatedSubfieldValue( $field );
						} else {
							$field['value'] = $this->convert_relationship_field( acf_get_value( self::ORIGINAL_ID, $field ), $field );

							$field['readonly'] = true;

							$message        = esc_attr__( 'This field is locked for editing because WPML will copy its value from the original language.', 'acfml' );
							$label          = Obj::propOr( '', 'label', $field );
							$field['label'] = sprintf( '%s <i class="otgs-ico-lock js-otgs-popover-tooltip" title="%s"></i>', $label, $message );
						}
					}
					break;
				case WPML_COPY_ONCE_CUSTOM_FIELD:
					if ( null === $field['value'] ) {
						// acf_get_value returns value or empty string if value not set yet.
						$field['value'] = $this->convert_relationship_field( acf_get_value( $post_id, $field ), $field );
					}
					if ( empty( $field['value'] ) && $this->is_field_on_translated_options_page( $post_id ) ) { // so if value not set yet, get value from original language.
						$field['value'] = $this->convert_relationship_field( acf_get_value( self::ORIGINAL_ID, $field ), $field );
					}
					break;
			}
		}

		return $field;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	private function updateTranslatedSubfieldValue( $field ) {
		if ( is_array( $field['value'] ) ) {
			foreach ( $field['value'] as $row => $pair ) {
				foreach ( $pair as $fieldHash => $fieldRealValue ) {
					$subfieldName = $this->getMatchingTranslatableSubFieldName( $field, $fieldHash );
					
					if ( $subfieldName ) {
						$field['value'][ $row ][ $fieldHash ] = get_option( $this->getTranslatedSubfieldName( $field['name'], $row, $subfieldName ) );
					}
				}
			}
		}
		return $field;
	}
	
	/**
	 * @param array  $field
	 * @param string $fieldHash
	 *
	 * @return string|null
	 */
	private function getMatchingTranslatableSubFieldName( $field, $fieldHash ) {
		
		// $matchesHash :: array -> bool
		$matchesHash = Relation::propEq( 'key', $fieldHash );

		// $isTranslatable :: array -> bool
		$isTranslatable = Relation::propEq( 'wpml_cf_preferences', WPML_TRANSLATE_CUSTOM_FIELD );

		// $getName :: array -> string|null
		$getName = Obj::prop( 'name' );

		return wpml_collect( Obj::propOr( [], 'sub_fields', $field ) )
			->filter( $matchesHash )
			->filter( $isTranslatable )
			->map( $getName )
			->first();
	}
	
	/**
	 * @param string $parentFieldName
	 * @param int    $row
	 * @param string $subFieldName
	 *
	 * @return string
	 */
	private function getTranslatedSubfieldName( $parentFieldName, $row, $subFieldName ) {
		return sprintf(
			'%s_%s_%d_%s',
			sprintf( self::TRANSLATED_ID_FORMAT, $this->sitepress->get_current_language() ),
			$parentFieldName,
			$row,
			$subFieldName
		);
	}


	/**
	 * Updates options page's value during save to assure when field is set to Copy, it always stores default value.
	 *
	 * @param mixed  $value          The field's value.
	 * @param mixed  $post_id        Post id or string identifying current option page.
	 * @param array  $field          Field metadata.
	 * @param string $original_value The field's value (again, the same as in first param).
	 *
	 * @return mixed
	 */
	public function overwrite_option_value( $value, $post_id = 0, $field = array(), $original_value = '' ) {
		if ( ! $this->is_repeater_field( $field )
				&& isset( $field['wpml_cf_preferences'] )
				&& WPML_COPY_CUSTOM_FIELD === $field['wpml_cf_preferences']
				&& $this->is_field_on_translated_options_page( $post_id )
		) {
			$value = $this->convert_relationship_field( acf_get_value( self::ORIGINAL_ID, $field ), $field );
		}
		return $value;
	}

	/**
	 * @param mixed $post_id Post id or string identifying current option page.
	 *
	 * @return bool
	 */
	private function is_field_on_translated_options_page( $post_id ) {
		$on_translated_page = false;
		if ( ! is_numeric( $post_id ) ) {
			$current_language   = $this->sitepress->get_current_language();
			$default_language   = $this->sitepress->get_default_language();
			$expected_post_id   = sprintf( self::TRANSLATED_ID_FORMAT, $current_language );
			$on_translated_page = $current_language !== $default_language && $expected_post_id === $post_id;
		}
		return $on_translated_page;
	}

	/**
	 * If field has numeric relationship value, replace with translated version.
	 *
	 * @param mixed $value Current field value.
	 * @param array $field ACF field.
	 *
	 * @return mixed
	 */
	private function convert_relationship_field( $value, $field ) {
		if ( is_numeric( $value ) ) {
			$current_language = $this->sitepress->get_current_language();
			$data             = $this->acfWorker->prepare_metadata( $value, $field['name'], self::ORIGINAL_ID, sprintf( self::TRANSLATED_ID_FORMAT, $current_language ) );
			$value            = $this->acfWorker->duplicate_post_meta( $value, $current_language, $data );
		}
		return $value;
	}

	/**
	 * Checks if given field has repeater field type.
	 *
	 * @param array $field ACF field data.
	 *
	 * @return bool
	 */
	private function is_repeater_field( $field ) {
		return isset( $field['type'] ) && 'repeater' === $field['type'];
	}

	/**
	 * @param mixed $post_id
	 *
	 * @return mixed|string
	 */
	public function append_language_code_for_option_pages( $post_id ) {
		if ( is_string( $post_id )
			&& ! is_numeric( $post_id )
			&& ! $this->is_id_a_restricted_word( $post_id )
			&& ! $this->id_starts_with_options( $post_id )
			&& ! $this->is_term_id( $post_id )
			&& ! $this->is_taxonomy_name( $post_id )
			&& ! $this->is_block_id( $post_id )
			&& ! $this->id_starts_with_user( $post_id )
			&& ! $this->is_widget_id( $post_id )
		) {
			$cl = acf_get_setting( 'current_language' );
			$dl = acf_get_setting( 'default_language' );

			if ( ! $this->id_ends_with_language_code( $post_id, $cl ) && $cl !== $dl ) {
				$post_id .= '_' . $cl;
			}
		}
		return $post_id;
	}

	/**
	 * @param mixed $post_id
	 *
	 * @return bool
	 */
	private function id_starts_with_options( $post_id ) {;
		return 'options' === substr( $post_id, 0, 7 );
	}
	
	/**
	 * @param string $post_id
	 *
	 * @return bool
	 */
	private function is_block_id( $post_id ) {
		return 'block_' === substr( $post_id, 0, 6 );
	}
	
	/**
	 * @param mixed $post_id
	 *
	 * @return bool
	 */
	private function id_starts_with_user( $post_id ) {
		return (bool) Str::startsWith( 'user_', $post_id );
	}
	
	/**
	 * @param mixed $post_id
	 * @param string $language_code
	 *
	 * @return bool
	 */
	private function id_ends_with_language_code( $post_id, $language_code ) {
		return '_' . $language_code === substr( $post_id, -3 );
	}
	
	/**
	 * @param mixed $post_id
	 *
	 * @return bool
	 */
	private function is_term_id( $post_id ) {
		return 'term_' === substr( $post_id, 0, 5 );
	}
	
	/**
	 * @param string $post_id
	 *
	 * @return bool
	 */
	private function is_taxonomy_name( $post_id ) {
		$taxonomies = get_taxonomies( [], 'names' );
		foreach ( $taxonomies as $taxonomy_name ) {
			if ( (bool) Str::startsWith( sprintf( '%s_', $taxonomy_name ), $post_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $post_id
	 *
	 * @return bool
	 */
	private function is_widget_id( $post_id ) {
		return 'widget_' === substr( $post_id, 0, 7 );
	}

	/**
	 * @param string $post_id
	 *
	 * @return bool
	 */
	private function is_id_a_restricted_word( $post_id ) {
		// I am using here array values only to describe why a key is a restricted word. It is kind of a comment
		// plus it is faster to lookup array keys than values.
		$restricted = [
			'new_post' => 'The post id is new_post so it is fake id used in acf_form function when creating new post with ACF fields.'
		];
		return array_key_exists( $post_id, $restricted );
	}
}
