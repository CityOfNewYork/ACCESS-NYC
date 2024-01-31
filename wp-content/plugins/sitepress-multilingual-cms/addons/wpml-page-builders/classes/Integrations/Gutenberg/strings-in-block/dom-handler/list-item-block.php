<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

class ListItemBlock extends StandardBlock {


	/**
	 * @inheritDoc
	 */
	public function applyStringTranslations( \WP_Block_Parser_Block $block, \DOMNode $element, $translation, $originalValue = null) {
		// For List item elements that contain child nodes, we will just replace the translation from the original text.
		if ( $element instanceof \DOMElement && $element->childNodes->length > 0 ) {
			$block->innerHTML = str_replace( $originalValue, $translation, $block->innerHTML );
			foreach ( $block->innerContent as &$inner_content ) {
				if ( $inner_content ) {
					$inner_content = str_replace( $originalValue, $translation, $inner_content );
				}
			}
			return $block;
		}

		return parent::applyStringTranslations( $block, $element, $translation, $originalValue );
	}
}
