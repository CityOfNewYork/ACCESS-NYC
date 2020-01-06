<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

class Collection implements StringsInBlock {

	/** @var StringsInBlock[] $parsers */
	private $parsers = [];

	/**
	 * @param StringsInBlock[] $parsers
	 */
	public function __construct( array $parsers ) {
		$this->parsers = $parsers;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	public function find( \WP_Block_Parser_Block $block ) {
		$strings = [];

		foreach ( $this->parsers as $parser ) {
			$strings = array_merge( $strings, $parser->find( $block ) );
		}

		/**
		 * String in block filter.
		 *
		 * When post with Gutenberg blocks is being send for translation
		 * WPML parses blocks to find translatable strings. With this filter
		 * you can register additional strings to be added to Translation
		 * Package and then translated
		 *
		 * @param array                  $strings already found strings.
		 * @param \WP_Block_Parser_Block $block block being parsed.
		 */
		return apply_filters( 'wpml_found_strings_in_block', $strings, $block );
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param array                  $string_translations
	 * @param string                 $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function update( \WP_Block_Parser_Block $block, array $string_translations, $lang ) {
		$originalInnerHTML = $block->innerHTML;
		foreach ( $this->parsers as $parser ) {
			$block = $parser->update( $block, $string_translations, $lang );
		}

		if ( $originalInnerHTML !== $block->innerHTML ) {
			$block->attrs['translatedWithWPMLTM'] = '1';
		}

		/**
		 * Filter to allow replacing Block attributes when translated post with block is saved.
		 *
		 * @param \WP_Block_Parser_Block $block               block being saved.
		 * @param array                  $string_translations array with string translations for current String Package.
		 * @param string                 $lang                language of translated post/block.
		 */
		return apply_filters( 'wpml_update_strings_in_block', $block, $string_translations, $lang );
	}
}
