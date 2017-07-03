<?php

class WPML_PB_Update_Shortcodes_In_Content {

	/** @var  WPML_PB_Shortcode_Strategy $strategy */
	private $strategy;
	/** @var WPML_PB_Shortcode_Encoding $encoding */
	private $encoding;

	private $new_content;
	private $string_translations;
	private $lang;

	public function __construct( WPML_PB_Shortcode_Strategy $strategy, WPML_PB_Shortcode_Encoding $encoding ) {
		$this->strategy = $strategy;
		$this->encoding = $encoding;
	}

	public function update( $translated_post_id, $original_post, $string_translations, $lang ) {
		$original_content = $original_post->post_content;
		$original_content = apply_filters( 'wpml_pb_shortcode_content_for_translation', $original_content, $original_post->ID );

		$new_translation = $this->update_content( $original_content, $string_translations, $lang );

		$translated_post     = get_post( $translated_post_id );
		$current_translation = isset( $translated_post->post_content ) ? $translated_post->post_content : '';
		$current_translation = apply_filters( 'wpml_pb_shortcode_content_for_translation', $current_translation, $translated_post_id );

		$translation_saved = apply_filters( 'wpml_pb_shortcodes_save_translation', false, $translated_post_id, $new_translation );

		if ( ! $translation_saved ) {
			if ( $new_translation != $original_content || '' === $current_translation ) {
				wp_update_post( array(
					'ID'           => $translated_post_id,
					'post_content' => $new_translation,
				) );
			}
		}
	}

	public function update_content( $original_content, $string_translations, $lang ) {
		$this->new_content         = $original_content;
		$this->string_translations = $string_translations;
		$this->lang                = $lang;

		$shortcode_parser = $this->strategy->get_shortcode_parser();
		$shortcodes       = $shortcode_parser->get_shortcodes( $original_content );

		foreach ( $shortcodes as $shortcode ) {
			$this->update_shortcodes( $shortcode );
			$this->update_shortcode_attributes( $shortcode );
		}

		return $this->new_content;
	}

	private function update_shortcodes( $shortcode_data ) {
		$encoding = $this->strategy->get_shortcode_tag_encoding( $shortcode_data['tag'] );
		$translation = $this->get_translation( $shortcode_data['content'], $encoding );
		$this->replace_string_with_translation( $shortcode_data['block'], $shortcode_data['content'], $translation );
	}

	private function update_shortcode_attributes( $shortcode_data ) {

		$shortcode_attribute = $shortcode_data['attributes'];

		$attributes              = (array) shortcode_parse_atts( $shortcode_attribute );
		$translatable_attributes = $this->strategy->get_shortcode_attributes( $shortcode_data['tag'] );
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attr => $attr_value ) {
				if ( in_array( $attr, $translatable_attributes, true ) ) {
					$encoding            = $this->strategy->get_shortcode_attribute_encoding( $shortcode_data['tag'], $attr );
					$translation         = $this->get_translation( $attr_value, $encoding );
					$translation         = $this->filter_attribute_translation( $translation );
					$shortcode_attribute = $this->replace_string_with_translation( $shortcode_attribute, $attr_value, $translation, true );
				}
			}
		}
	}

	private function replace_string_with_translation( $block, $original, $translation, $is_attribute = false ) {
		$new_block = $block;
		if ( $translation ) {
			if ( $is_attribute ) {
				$pattern   = '/(["\'])' . preg_quote( $original, '/' ) . '(["\'])/';
				$new_block = preg_replace( $pattern, '${1}' . $translation . '${2}', $block );
			} else {
				$new_block = str_replace( ']' . $original . '[', ']' . $translation . '[', $block );
			}
			$this->new_content = str_replace( $block, $new_block, $this->new_content );
		}

		return $new_block;
	}

	private function get_translation( $original, $encoding = false ) {
		$decoded_original = $this->encoding->decode( $original, $encoding );

		$translation = null;

		if ( is_array( $decoded_original ) ) {

			$translation = array();
			foreach ( $decoded_original as $key => $data ) {
				if ( $data['translate'] ) {
					$translated_data = $this->get_translation( $data['value'], '' );
					if ( $translated_data ) {
						$translation[ $key ] = $translated_data;
					} else {
						$translation[ $key ] = $data['value'];
					}
				} else {
					$translation[ $key ] = $data['value'];
				}
			}

		} else {

			$string_name = md5( $decoded_original );
			if ( isset( $this->string_translations[ $string_name ][ $this->lang ] ) && $this->string_translations[ $string_name ][ $this->lang ]['status'] == ICL_TM_COMPLETE ) {
				$translation = $this->string_translations[ $string_name ][ $this->lang ]['value'];
			}
		}

		if ( $translation ) {
			$translation = $this->encoding->encode( $translation, $encoding );
		}

		return $translation;
	}

	/**
	 * @param $translation
	 *
	 * @return string
	 */
	private function filter_attribute_translation( $translation ) {
		$translation = htmlspecialchars( $translation );
		$translation = str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $translation );

		return $translation;
	}
}

