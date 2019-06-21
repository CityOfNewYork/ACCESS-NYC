<?php

class WPML_ACF_Blocks {

	public function init_hooks() {
		add_filter( 'wpml_found_strings_in_block', array( $this, 'add_block_data_attribute_strings' ), 10, 2 );
		add_filter( 'wpml_update_strings_in_block', array( $this, 'update_block_data_attribute'), 10, 3 );
	}

	/**
	 * @param array                 $strings strings in block
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return array $strings
	 */
	public function add_block_data_attribute_strings( array $strings, WP_Block_Parser_Block $block ) {

		if ( $this->is_acf_block( $block ) && isset( $block->attrs['data'] ) ) {

			if ( !is_array( $block->attrs['data'] ) ) {
				$block->attrs['data'] = array( $block->attrs['data'] );
			}

			foreach ( $block->attrs['data'] as $field_name => $text ) {

				if ( $this->must_skip( $field_name, $text ) ) {
					continue;
				}

				$type = $this->get_text_type( $text );

				$strings[] = (object) array(
					'id'    => $this->get_string_hash( $block->blockName, $text ),
					'name'  => $this->get_string_name( $block,  $field_name ),
					'value' => $text,
					'type'  => $type,
				);
			}
		}

		return $strings;
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

				$string_hash = $this->get_string_hash( $block->blockName, $text );

				if ( isset( $string_translations[ $string_hash ][ $lang ]['status'] )
				     && $string_translations[ $string_hash ][ $lang ]['status'] == ICL_TM_COMPLETE
				     && isset( $string_translations[ $string_hash ][ $lang ]['value'] )
				) {
					$block->attrs['data'][ $field_name ] = $string_translations[ $string_hash ][ $lang ]['value'];
				}
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
	 * @param $text ACF field value.
	 *
	 * @return string
	 */
	private function get_text_type( $text ) {
		$type = 'LINE';
		if ( strpos( $text, "\n" ) !== false ) {
			$type = 'AREA';
		}
		if ( strpos( $text, '<' ) !== false ) {
			$type = 'VISUAL';
		}
		return $type;
	}
	/**
	 * @param $field_name ACF field name.
	 * @param $text       ACF field value.
	 *
	 * @return bool
	 */
	private function must_skip( $field_name, $text ) {
		return $this->is_system_field( $field_name ) || ( ! is_string( $text ) && ! is_numeric( $text ) );
	}
}
