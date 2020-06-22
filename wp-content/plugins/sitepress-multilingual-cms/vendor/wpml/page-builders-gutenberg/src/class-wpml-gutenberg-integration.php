<?php

/**
 * Class WPML_Gutenberg_Integration
 */
class WPML_Gutenberg_Integration implements \WPML\PB\Gutenberg\Integration {

	const PACKAGE_ID              = 'Gutenberg';
	const GUTENBERG_OPENING_START = '<!-- wp:';
	const GUTENBERG_CLOSING_START = '<!-- /wp:';
	const CLASSIC_BLOCK_NAME      = 'core/classic-block';

	/**
	 * @var WPML\PB\Gutenberg\StringsInBlock\StringsInBlock
	 */
	private $strings_in_blocks;

	/**
	 * @var WPML_Gutenberg_Config_Option
	 */
	private $config_option;

	/**
	 * @var WPML_Gutenberg_Strings_Registration $strings_registration
	 */
	private $strings_registration;

	public function __construct(
		WPML\PB\Gutenberg\StringsInBlock\StringsInBlock $strings_in_block,
		WPML_Gutenberg_Config_Option $config_option,
		WPML_Gutenberg_Strings_Registration $strings_registration
	) {
		$this->strings_in_blocks    = $strings_in_block;
		$this->config_option        = $config_option;
		$this->strings_registration = $strings_registration;
	}

	public function add_hooks() {
		add_filter( 'wpml_page_builder_support_required', array( $this, 'page_builder_support_required' ), 10, 1 );
		add_action( 'wpml_page_builder_register_strings', array( $this, 'register_strings' ), 10, 2 );
		add_action( 'wpml_page_builder_string_translated', array( $this, 'string_translated' ), 10, 5 );
		add_filter( 'wpml_config_array', array( $this, 'wpml_config_filter' ) );
		add_filter( 'wpml_pb_should_body_be_translated', array( $this, 'should_body_be_translated_filter' ), PHP_INT_MAX, 3 );
		add_filter( 'wpml_get_translatable_types', array( $this, 'remove_package_strings_type_filter' ), 11 );
	}

	/**
	 * @param array $plugins
	 *
	 * @return array
	 */
	function page_builder_support_required( $plugins ) {
		$plugins[] = self::PACKAGE_ID;

		return $plugins;
	}

	/**
	 * @param WP_Post $post
	 * @param array $package_data
	 */
	function register_strings( WP_Post $post, $package_data ) {

		if ( ! $this->is_gutenberg_post( $post ) ) {
			return;
		}

		if ( self::PACKAGE_ID === $package_data['kind'] ) {
			$this->strings_registration->register_strings( $post, $package_data );
		}
	}

	/**
	 * @param WP_Block_Parser_Block|array $block
	 *
	 * @return WP_Block_Parser_Block
	 */
	public static function sanitize_block( $block ) {
		if ( ! $block instanceof WP_Block_Parser_Block ) {

			if ( empty( $block['blockName'] ) ) {
				$block['blockName'] = self::CLASSIC_BLOCK_NAME;
			}

			$block = new WP_Block_Parser_Block( $block['blockName'], $block['attrs'], $block['innerBlocks'], $block['innerHTML'], $block['innerContent'] );
		}

		return $block;
	}

	/**
	 * @param string $package_kind
	 * @param int $translated_post_id
	 * @param WP_Post $original_post
	 * @param array $string_translations
	 * @param string $lang
	 */
	public function string_translated(
		$package_kind,
		$translated_post_id,
		$original_post,
		$string_translations,
		$lang
	) {

		if ( self::PACKAGE_ID === $package_kind ) {
			$blocks = self::parse_blocks( $original_post->post_content );

			$blocks = $this->update_block_translations( $blocks, $string_translations, $lang );

			$content = '';
			foreach ( $blocks as $block ) {
				$content .= $this->render_block( $block );
			}

			wpml_update_escaped_post( [ 'ID' => $translated_post_id, 'post_content' => $content ], $lang );
		}

	}

	/**
	 * @param array $blocks
	 * @param array $string_translations
	 * @param string $lang
	 *
	 * @return array
	 */
	private function update_block_translations( $blocks, $string_translations, $lang ) {
		foreach ( $blocks as &$block ) {

			$block = self::sanitize_block( $block );
			$block = $this->strings_in_blocks->update( $block, $string_translations, $lang );

			if ( isset( $block->innerBlocks ) ) {
				$block->innerBlocks = $this->update_block_translations(
					$block->innerBlocks,
					$string_translations,
					$lang
				);
			}
		}

		return $blocks;
	}

	/**
	 * @param array|WP_Block_Parser_Block $block
	 *
	 * @return string
	 */
	public static function render_block( $block ) {
		$content = '';

		$block = self::sanitize_block( $block );

		if ( self::CLASSIC_BLOCK_NAME !== $block->blockName ) {
			$block_content = self::render_inner_HTML( $block );
			$block_type    = preg_replace( '/^core\//', '', $block->blockName );

			$block_attributes = '';
			if ( self::has_non_empty_attributes( $block ) ) {
				$block_attributes = ' ' . json_encode( $block->attrs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
			}
			$content .= self::GUTENBERG_OPENING_START . $block_type . $block_attributes . ' ';

			if ( $block_content ) {
				$content .= '-->' . $block_content . self::GUTENBERG_CLOSING_START . $block_type . ' -->';
			} else {
				$content .= '/-->';
			}
		} else {
			$content = wpautop( $block->innerHTML );
		}

		return $content;

	}

	public static function has_non_empty_attributes( WP_Block_Parser_Block $block ) {
		return (bool) ( (array) $block->attrs );
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return string
	 */
	private static function render_inner_HTML( $block ) {

		if ( isset ( $block->innerBlocks ) && count( $block->innerBlocks ) ) {

			if ( isset( $block->innerContent ) ) {
				$content = self::render_inner_HTML_with_innerContent( $block );
			} else {
				$content = self::render_inner_HTML_with_guess_parts( $block );
			}

		} else {
			$content = $block->innerHTML;
		}

		return $content;

	}

	/**
	 * Since Gutenberg 4.2.0 and WP 5.0.0 we have a new
	 * property WP_Block_Parser_Block::$innerContent which
	 * provides the sequence of inner elements:
	 * strings or null if it's an inner block.
	 *
	 * @see WP_Block_Parser_Block::$innerContent
	 *
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return string
	 */
	private static function render_inner_HTML_with_innerContent( $block ) {
		$content           = '';
		$inner_block_index = 0;

		foreach ( $block->innerContent as $inner_content ) {
			if ( is_string( $inner_content ) ) {
				$content .= $inner_content;
			} else {
				$content .= self::render_block( $block->innerBlocks[ $inner_block_index ] );
				$inner_block_index++;
			}
		}

		return $content;
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return string
	 */
	private static function render_inner_HTML_with_guess_parts( $block ) {
		$inner_html_parts = self::guess_inner_HTML_parts( $block );

		$content = $inner_html_parts[0];

		foreach ( $block->innerBlocks as $inner_block ) {
			$content .= self::render_block( $inner_block );
		}

		$content .= $inner_html_parts[1];

		return $content;
	}

	/**
	 * The gutenberg parser prior to version 4.2.0 (Gutenberg) and 5.0.0 (WP)
	 * doesn't handle inner blocks correctly.
	 * It should really return the HTML before and after the blocks
	 * We're just guessing what it is here
	 * The usual innerHTML would be: <div class="xxx"></div>
	 * The columns block also includes new lines: <div class="xxx">\n\n</div>
	 * So we try to split at ></ and also include white space and new lines between the tags
	 *
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	private static function guess_inner_HTML_parts( $block ) {
		$inner_HTML = $block->innerHTML;

		$parts = array( $inner_HTML, '' );

		switch( $block->blockName ) {
			case 'core/media-text':
				$html_to_find = '<div class="wp-block-media-text__content">';
				$pos          = mb_strpos( $inner_HTML, $html_to_find ) + mb_strlen( $html_to_find );
				$parts        = array(
					mb_substr( $inner_HTML, 0, $pos ),
					mb_substr( $inner_HTML, $pos )
				);
				break;

			default:
				preg_match( '#>\s*</#', $inner_HTML, $matches );

				if ( count( $matches ) === 1 ) {
					$parts = explode( $matches[0], $inner_HTML );
					if ( count( $parts ) === 2 ) {
						$match_mid_point = 1 + ( mb_strlen( $matches[0] ) - 3 ) / 2;
						// This is the first ">" char plus half the remaining between the tags

						$parts[0] .= mb_substr( $matches[0], 0, $match_mid_point );
						$parts[1] = mb_substr( $matches[0], $match_mid_point ) . $parts[1];
					}
				}
				break;
		}

		return $parts;
	}

	/**
	 * @param array $config_data
	 *
	 * @return array
	 */
	public function wpml_config_filter( $config_data ) {
		$this->config_option->update_from_config( $config_data );

		return $config_data;
	}

	/**
	 * @param bool    $translate
	 * @param WP_Post $post
	 * @param string  $context
	 *
	 * @return bool
	 */
	public function should_body_be_translated_filter( $translate, WP_Post $post, $context = '' ) {
		if ( 'translate_images_in_post_content' === $context && $this->is_gutenberg_post( $post ) ) {
			$translate = true;
		}

		return $translate;
	}

	/**
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	private function is_gutenberg_post( WP_Post $post ) {
		return (bool) preg_match( '/' . self::GUTENBERG_OPENING_START . '/', $post->post_content );
	}

	public static function parse_blocks( $content ) {
		global $wp_version;
		if ( version_compare( $wp_version, '5.0-beta1', '>=' ) ) {
			return parse_blocks( $content );
		} else {
			return gutenberg_parse_blocks( $content );
		}
	}

	/**
	 * Remove Gutenberg (string package) from translation dashboard filters
	 *
	 * @param array $types
	 *
	 * @return mixed
	 */
	public function remove_package_strings_type_filter( $types ) {

		if ( array_key_exists( 'gutenberg', $types ) ) {
			unset( $types['gutenberg'] );
		}

		return $types;
	}
}
