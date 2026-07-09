<?php

use WPML\FP\Obj;

class WPML_ACF_Blocks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

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
	public function add_hooks() {
		add_filter( 'wpml_found_strings_in_block', [ $this, 'add_block_data_attribute_strings' ], 10, 2 );
		add_filter( 'wpml_update_strings_in_block', [ $this, 'update_block_data_attribute' ], 10, 3 );
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
				$block->attrs['data'] = [ $block->attrs['data'] ];
			}

			$addStringRecursive = function( $value, $name ) use ( $block, &$strings, &$addStringRecursive ) {
				$type = $this->get_text_type( $value );

				if ( 'array' === $type ) {
					foreach ( $value as $innerName => $innerValue ) {
						$addStringRecursive( $innerValue, $name . '/' . $innerName );
					}
				} elseif ( ! $this->must_skip( $name, $value ) ) {
					$strings[] = $this->add_string( $block, $value, $name, $type );
				}
			};

			foreach ( $block->attrs['data'] as $fieldName => $fieldValue ) {
				$addStringRecursive( $fieldValue, $fieldName );
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
		return (object) [
			'id'    => $this->get_string_hash( $block->blockName, $text ),
			'name'  => $this->get_string_name( $block, $field_name ),
			'value' => $text,
			'type'  => $type,
		];
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
				$block = $this->set_block_field_translation_recursive( $block, $string_translations, $lang, $text, [ $field_name ] );
			}
		}
		return $block;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 * @param array                 $stringTranslations
	 * @param string                $language
	 * @param string|array          $value
	 * @param array                 $stringPath
	 *
	 * @return mixed
	 */
	private function set_block_field_translation_recursive( $block, $stringTranslations, $language, $value, $stringPath ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $innerPath => $innerValue ) {
				$innerStringPath   = $stringPath;
				$innerStringPath[] = $innerPath;
				$block             = $this->set_block_field_translation_recursive( $block, $stringTranslations, $language, $innerValue, $innerStringPath );
			}
		} else {
			$block = $this->set_block_field_translation( $block, $stringTranslations, $language, $value, $stringPath );
		}
		return $block;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 * @param array                 $stringTranslations
	 * @param string                $language
	 * @param string                $value
	 * @param array                 $stringPath
	 *
	 * @return mixed
	 */
	private function set_block_field_translation( $block, $stringTranslations, $language, $value, $stringPath ) {
		$stringHash = $this->get_string_hash( $block->blockName, $value );

		if (
			isset( $stringTranslations[ $stringHash ][ $language ]['status'] )
			&& ICL_TM_COMPLETE === (int) $stringTranslations[ $stringHash ][ $language ]['status']
			&& isset( $stringTranslations[ $stringHash ][ $language ]['value'] )
			&& Obj::hasPath( $stringPath, $block->attrs['data'] )
		) {
			$block->attrs['data'] = Obj::set(
				Obj::lensPath( $stringPath ),
				$stringTranslations[ $stringHash ][ $language ]['value'],
				$block->attrs['data']
			);
		}

		return $block;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return bool
	 */
	private function is_acf_block( WP_Block_Parser_Block $block ) {
		return strpos( $block->blockName, 'acf/' ) === 0 ||
			function_exists( 'acf_has_block_type' ) && acf_has_block_type( $block->blockName );
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
		} elseif ( filter_var( $text, FILTER_VALIDATE_URL ) ) {
			$type = 'LINK';
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
			return WPML_TRANSLATE_CUSTOM_FIELD === (int) $acfField['wpml_cf_preferences'];
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

