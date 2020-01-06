<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

interface StringsInBlock {

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	public function find( \WP_Block_Parser_Block $block );

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param array                 $string_translations
	 * @param string                $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function update( \WP_Block_Parser_Block $block, array $string_translations, $lang );
}
