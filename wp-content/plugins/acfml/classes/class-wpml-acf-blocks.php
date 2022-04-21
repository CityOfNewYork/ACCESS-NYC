<?php

class WPML_ACF_Blocks {

	/**
	 * @var WPML_Post_Translation
	 */
	private $wpml_post_translations;

	/**
	 * WPML_ACF_Blocks constructor.
	 *
	 * @param WPML_Post_Translation $wpml_post_translations
	 */
	public function __construct( WPML_Post_Translation $wpml_post_translations ) {
		$this->wpml_post_translations = $wpml_post_translations;
	}

	/**
	 * Initialize hooks.
	 */
	public function init_hooks() {
		add_filter( 'wpml_found_strings_in_block', array( $this, 'add_block_data_attribute_strings' ), 10, 2 );
		add_filter( 'wpml_update_strings_in_block', array( $this, 'update_block_data_attribute' ), 10, 3 );
	}

	/**
	 * @param array                 $strings Strings in block.
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return array $strings
	 */
	public function add_block_data_attribute_strings( array $strings, WP_Block_Parser_Block $block ) {

		if ( $this->is_acf_block( $block ) && isset( $block->attrs['data'] ) ) {

			if ( ! is_array( $block->attrs['data'] ) ) {
				$block->attrs['data'] = array( $block->attrs['data'] );
			}

			foreach ( $block->attrs['data'] as $field_name => $text ) {

				if ( $this->must_skip( $field_name, $text ) ) {
					continue;
				}

				$type = $this->get_text_type( $text );

				if ( 'array' === $type ) {
					foreach ( $text as $inner_field_name => $inner_text ) {
						$inner_type = $this->get_text_type( $inner_text );
						$strings[]  = $this->add_string( $block, $inner_text, $field_name . '/' . $inner_field_name, $inner_type );
					}
				} else {
					$strings[] = $this->add_string( $block, $text, $field_name, $type );
				}
			}
		}

		return $strings;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 * @param string                $text
	 * @param string                $field_name
	 * @param string                $type
	 *
	 * @return object
	 */
	private function add_string( $block, $text, $field_name, $type ) {
		return (object) array(
			'id'    => $this->get_string_hash( $block->blockName, $text ),
			'name'  => $this->get_string_name( $block, $field_name ),
			'value' => $text,
			'type'  => $type,
		);
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 * @param array                 $string_translations
	 * @param string                $lang
	 *
	 * @return WP_Block_Parser_Block
	 */
	public function update_block_data_attribute( WP_Block_Parser_Block $block, array $string_translations, $lang ) {
		if ( $this->is_acf_block( $block ) && isset( $block->attrs['data'] ) ) {
			foreach ( $block->attrs['data'] as $field_name => $text ) {
				if ( $this->is_system_field( $field_name ) ) {
					continue;
				}
				$block = $this->get_block_field_translation_recursive( $block, $string_translations, $lang, $text, $field_name );
			}
		}
		return $block;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 * @param array                 $string_translations
	 * @param string                $lang
	 * @param string|array          $text
	 * @param string                $field_name
	 * @param null|string           $inner_field_name
	 *
	 * @return mixed
	 */
	private function get_block_field_translation_recursive( $block, $string_translations, $lang, $text, $field_name, $inner_field_name = null ) {
		if ( is_array( $text ) ) {
			foreach ( $text as $inner_field_name => $inner_text ) {
				$block = $this->get_block_field_translation_recursive( $block, $string_translations, $lang, $inner_text, $field_name, $inner_field_name );
			}
		} else {
			$block = $this->get_block_field_translation( $block, $string_translations, $lang, $text, $field_name, $inner_field_name );
		}
		return $block;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 * @param array                 $string_translations
	 * @param string                $lang
	 * @param string                $text
	 * @param string                $field_name
	 * @param null|string           $inner_field_name
	 *
	 * @return mixed
	 */
	private function get_block_field_translation( $block, $string_translations, $lang, $text, $field_name, $inner_field_name = null ) {
		$string_hash = $this->get_string_hash( $block->blockName, $text );

		if ( isset( $string_translations[ $string_hash ][ $lang ]['status'] )
			 && ICL_TM_COMPLETE === (int) $string_translations[ $string_hash ][ $lang ]['status']
			 && isset( $string_translations[ $string_hash ][ $lang ]['value'] )
		) {
			if ( $inner_field_name ) {
				$block->attrs['data'][ $field_name ][ $inner_field_name ] = $string_translations[ $string_hash ][ $lang ]['value'];
			} else {
				$block->attrs['data'][ $field_name ] = $string_translations[ $string_hash ][ $lang ]['value'];
			}
		}

		return $block;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return bool
	 */
	private function is_acf_block( WP_Block_Parser_Block $block ) {
		return strpos( $block->blockName, 'acf/' ) === 0;
	}

	/**
	 * @param string $block_name
	 * @param string $text
	 *
	 * @return string
	 */
	private function get_string_hash( $block_name, $text ) {
		return md5( $block_name . $text );
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 * @param string                $field_name
	 *
	 * @return string
	 */
	private function get_string_name( WP_Block_Parser_Block $block, $field_name ) {
		return $block->blockName . '/' . $field_name;
	}

	/**
	 * @param string $field_name
	 *
	 * @return bool
	 */
	private function is_system_field( $field_name ) {
		return strpos( $field_name, '_' ) === 0;
	}

	/**
	 * @param string|array $text ACF field value.
	 *
	 * @return string
	 */
	private function get_text_type( $text ) {
		$type = 'LINE';
		if ( is_array( $text ) ) {
			$type = 'array';
		} elseif ( strip_tags( $text ) !== $text ) {
			$type = 'VISUAL';
		} elseif ( strpos( $text, "\n" ) !== false ) {
			$type = 'AREA';
		}
		return $type;
	}
	/**
	 * @param string $fieldName ACF field name.
	 * @param string $text       ACF field value.
	 *
	 * @return bool
	 */
	private function must_skip( $fieldName, $text ) {
		return $this->is_system_field( $fieldName ) ||
			$this->valueIsNotTranslatable( $text ) ||
			! $this->isTranslatableInPreferences( $fieldName );
	}

	/**
	 * Checks if ACF field translation preferences is set to Translate or Copy once.
	 *
	 * @param string $fieldName ACF field name.
	 *
	 * @return bool
	 */
	private function isTranslatableInPreferences( $fieldName ) {
		$acfField = acf_get_field( $fieldName );
		if ( ! $acfField ) {
			$acfField = $this->maybeGetSubfield( $fieldName );
		}
		if ( isset( $acfField['wpml_cf_preferences'] ) ) {
			return (int) $acfField['wpml_cf_preferences'] === WPML_TRANSLATE_CUSTOM_FIELD;
		}
		return true;
	}

	/**
	 * Split field name by "_(digit)_" and try to return ACF field object for last part.
	 *
	 * Handles cases for repeater and flexible subfields.
	 *
	 * @param string $fieldName      Processed field name.
	 *
	 * @return array|false ACF field object (array) or false.
	 */
	private function maybeGetSubfield( $fieldName ) {
		$fieldNameParts = preg_split( '/_\d_/', $fieldName );
		if ( is_array( $fieldNameParts ) && 1 < count( $fieldNameParts ) ) {
			return acf_get_field( end( $fieldNameParts ) );
		}
		return false;
	}

	/**
	 * Checks if field value is in the format supported by Translation Editor.
	 *
	 * @param mixed $text
	 *
	 * @return bool
	 */
	private function valueIsNotTranslatable( $text ) {
		return ! is_string( $text ) &&
				! is_numeric( $text ) &&
				! is_array( $text );
	}

}

