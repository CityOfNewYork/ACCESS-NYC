<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

class ListItemBlock extends StandardBlock {


	/**
	 * @inheritDoc
	 */
	public function applyStringTranslations( \WP_Block_Parser_Block $block, \DOMNode $element, $translation, $originalValue = null) {
		// For List item elements that contain child nodes, we will just replace the translation from the original text.
		if ( $element instanceof \DOMElement && $element->childNodes->length > 0 ) {
			$originalValueVariations = $this->getOriginalVariationsForBrAndImgTags( $originalValue );

			$block->innerHTML = str_replace( $originalValueVariations, $translation, $block->innerHTML );
			foreach ( $block->innerContent as &$inner_content ) {
				if ( $inner_content ) {
					$inner_content = str_replace( $originalValueVariations, $translation, $inner_content );
				}
			}
			return $block;
		}

		return parent::applyStringTranslations( $block, $element, $translation, $originalValue );
	}

	/**
	 * This will provide an array of possible searches to apply the translation.
	 * It will make both versions of self-closing tags (with or without slash).
	 *
	 * @param string $originalValue
	 *
	 * @return array
	 */
	private function getOriginalVariationsForBrAndImgTags( $originalValue ) {
		$extractInsideBrOrImgTagsPattern = '/<(br[^>\/\s]*|img[^>]*[^>\/\s])(?:\s*\/)?\s*>/';

		preg_match( $extractInsideBrOrImgTagsPattern, $originalValue, $matches );

		if ( $matches ) {
			return array_unique(
				array_filter(
					[
						$originalValue,
						preg_replace( $extractInsideBrOrImgTagsPattern, '<${1}/>', $originalValue ), // With self-closing tags.
						preg_replace( $extractInsideBrOrImgTagsPattern, '<${1}>', $originalValue ), // With no-self-closing tags.
					]
				)
			);
		}

		return [ $originalValue ];
	}
}
