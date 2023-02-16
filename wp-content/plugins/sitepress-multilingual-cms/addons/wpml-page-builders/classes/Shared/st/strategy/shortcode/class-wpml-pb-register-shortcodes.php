<?php

use WPML\PB\Shortcode\StringCleanUp;

/**
 * Class WPML_PB_Register_Shortcodes
 */
class WPML_PB_Register_Shortcodes {

	private $handle_strings;
	/** @var  WPML_PB_Shortcode_Strategy $shortcode_strategy */
	private $shortcode_strategy;
	/** @var  WPML_PB_Shortcode_Encoding $encoding */
	private $encoding;
	/** @var WPML_PB_Reuse_Translations_By_Strategy|null $reuse_translations */
	private $reuse_translations;

	/** @var StringCleanUp|null */
	private $existingStrings;

	/** @var  int $location_index */
	private $location_index;

	/**
	 * @param WPML_PB_String_Registration                 $handle_strings
	 * @param WPML_PB_Shortcode_Strategy                  $shortcode_strategy
	 * @param WPML_PB_Shortcode_Encoding                  $encoding
	 * @param WPML_PB_Reuse_Translations_By_Strategy|null $reuse_translations
	 */
	public function __construct(
		WPML_PB_String_Registration $handle_strings,
		WPML_PB_Shortcode_Strategy $shortcode_strategy,
		WPML_PB_Shortcode_Encoding $encoding,
		WPML_PB_Reuse_Translations_By_Strategy $reuse_translations = null
	) {
		$this->handle_strings         = $handle_strings;
		$this->shortcode_strategy     = $shortcode_strategy;
		$this->encoding               = $encoding;
		$this->reuse_translations     = $reuse_translations;
	}

	/**
	 * @param string|int    $post_id
	 * @param string        $content
	 * @param StringCleanUp $externalStringCleanUp
	 *
	 * @return bool
	 */
	public function register_shortcode_strings(
		$post_id,
		$content,
		StringCleanUp $externalStringCleanUp = null
	) {

		$any_registered = false;

		$this->location_index = 1;

		$content = apply_filters( 'wpml_pb_shortcode_content_for_translation', $content, $post_id );
		$content = WPML_PB_Shortcode_Content_Wrapper::maybeWrap( $content, $this->shortcode_strategy->get_shortcodes() );

		$shortcode_parser      = $this->shortcode_strategy->get_shortcode_parser();
		$shortcodes            = $shortcode_parser->get_shortcodes( $content );
		$this->existingStrings = $externalStringCleanUp ?: new StringCleanUp( $post_id, $this->shortcode_strategy );

		if ( $this->reuse_translations ) {
			$this->reuse_translations->set_original_strings( $this->existingStrings->get() );
		}

		foreach ( $shortcodes as $shortcode ) {

			if ( $this->should_handle_content( $shortcode ) ) {
				$shortcode_content  = $shortcode['content'];
				$encoding           = $this->shortcode_strategy->get_shortcode_tag_encoding( $shortcode['tag'] );
				$encoding_condition = $this->shortcode_strategy->get_shortcode_tag_encoding_condition( $shortcode['tag'] );
				$type               = $this->shortcode_strategy->get_shortcode_tag_type( $shortcode['tag'] );
				$shortcode_content  = $this->encoding->decode( $shortcode_content, $encoding, $encoding_condition );
				$any_registered     = $this->register_string( $post_id, $shortcode_content, $shortcode, 'content', $type ) || $any_registered;
			}

			$attributes              = (array) shortcode_parse_atts( $shortcode['attributes'] );
			$translatable_attributes = $this->shortcode_strategy->get_shortcode_attributes( $shortcode['tag'] );
			if ( ! empty( $attributes ) ) {
				foreach ( $attributes as $attr => $attr_value ) {
					if ( in_array( $attr, $translatable_attributes, true ) ) {
						$encoding   = $this->shortcode_strategy->get_shortcode_attribute_encoding( $shortcode['tag'], $attr );
						$type       = $this->shortcode_strategy->get_shortcode_attribute_type( $shortcode['tag'], $attr );
						$attr_value = $this->encoding->decode( $attr_value, $encoding );

						$any_registered = $this->register_string( $post_id, $attr_value, $shortcode, $attr, $type ) || $any_registered;
					}
				}
			}
		}

		if ( $this->reuse_translations ) {
			$this->reuse_translations->find_and_reuse( $post_id, $this->existingStrings->get() );
		}

		if( ! $externalStringCleanUp ) {
			$this->existingStrings->cleanUp();
			$this->mark_post_as_migrate_location_done( $post_id );
		}

		return $any_registered;
	}

	/**
	 * @param array $shortcode
	 *
	 * @return bool
	 */
	private function should_handle_content( $shortcode ) {
		$tag = $shortcode['tag'];

		$handle_content = ! (
			$this->shortcode_strategy->get_shortcode_ignore_content( $tag )
			|| in_array(
				$this->shortcode_strategy->get_shortcode_tag_type( $tag ),
				array(
					'media-url',
					'media-ids',
				),
				true
			)
		);

		/**
		 * Allow page builders to override if the shortcode should be handled as a translatable string.
		 *
		 * @since 4.2
		 * @param bool $handle_content.
		 * @param array $shortcode {
		 *
		 *      @type string $tag.
		 *      @type string $content.
		 *      @type string $attributes.
		 * }
		 */
		return apply_filters( 'wpml_pb_should_handle_content', $handle_content, $shortcode );
	}

	function get_updated_shortcode_string_title( $string_id, $shortcode, $attribute ) {
		$title = $this->shortcode_strategy->get_shortcode_attribute_label( $shortcode['tag'], $attribute );
		if ( $title ) {
			return $title;
		}

		$current_title = $this->get_shortcode_string_title( $string_id );

		$current_title_parts = explode( ':', $current_title );
		$current_title_parts = array_map( 'trim', $current_title_parts );

		$shortcode_tag = $shortcode['tag'];
		if ( isset( $current_title_parts[1] ) ) {
			$shortcode_attributes = explode( ',', $current_title_parts[1] );
			$shortcode_attributes = array_map( 'trim', $shortcode_attributes );
		}
		$shortcode_attributes[] = $attribute;
		sort( $shortcode_attributes );
		$shortcode_attributes = array_unique( $shortcode_attributes );

		return $shortcode_tag . ': ' . implode( ', ', $shortcode_attributes );
	}

	function get_shortcode_string_title( $string_id ) {
		return $this->handle_strings->get_string_title( $string_id );
	}

	public function register_string( $post_id, $content, $shortcode, $attribute, $editor_type ) {
		$string_id = 0;

		if ( is_array( $content ) ) {
			foreach ( $content as $key => $data ) {
				if ( $data['translate'] ) {
					$this->register_string( $post_id, $data['value'], $shortcode, $attribute . ' ' . $key, $editor_type );
				}
			}
		} else {
			if ( $this->existingStrings ) {
				$this->existingStrings->remove( $content );
			}
			try {
				$string_id    = $this->handle_strings->get_string_id_from_package( $post_id, $content );
				$string_title = $this->get_updated_shortcode_string_title( $string_id, $shortcode, $attribute );
				$string_id    = $this->handle_strings->register_string( $post_id, $content, $editor_type, $string_title, '', $this->location_index );
				if ( $string_id ) {
					$this->location_index ++;
				}
			} catch ( Exception $exception ) {
				$string_id = 0;
			}
		}

		return $string_id !== 0;
	}

	/**
	 * @param int $post_id
	 */
	private function mark_post_as_migrate_location_done( $post_id ) {
		update_post_meta( $post_id, WPML_PB_Integration::MIGRATION_DONE_POST_META, true );
	}


}
