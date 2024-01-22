<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

use WPML\FP\Str;
use WPML\FP\Obj;
use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\DOMHandle;
use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\HtmlBlock;
use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\StandardBlock;
use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\ListBlock;
use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\ListItemBlock;
use WPML\PB\Gutenberg\XPath;

class HTML extends Base {

	const LIST_BLOCK_NAME      = 'core/list';
	const LIST_ITEM_BLOCK_NAME = 'core/list-item';
	const HTML_BLOCK_NAME      = 'core/html';

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	public function find( \WP_Block_Parser_Block $block ) {
		$strings = array();

		$block_queries = $this->get_block_queries( $block );

		if ( is_array( $block_queries ) && isset( $block->innerHTML ) ) {
			$dom_handle = $this->get_dom_handler( $block );
			$xpath      = $dom_handle->getDomxpath( $block->innerHTML );

			foreach ( $block_queries as $blockQuery ) {
				list( $query, $definedType, $label ) = XPath::parse( $blockQuery );
				$elements                            = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, $type ) = $dom_handle->getPartialInnerHTML( $element );
					if ( $text ) {
						$string_id = $this->get_string_id( $block->blockName, $text );
						$strings[] = $this->build_string(
							$string_id,
							$label ?: $this->get_block_label( $block ),
							$text,
							$definedType ? $definedType : $type
						);
					}
				}
			}
		} else {

			$string_id = $this->get_block_string_id( $block );
			if ( $string_id ) {
				$strings[] = $this->build_string(
					$string_id,
					$this->get_block_label( $block ),
					$block->innerHTML,
					'VISUAL'
				);
			}
		}

		return $strings;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param array                  $string_translations
	 * @param string                 $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function update( \WP_Block_Parser_Block $block, array $string_translations, $lang ) {

		$block_queries = $this->get_block_queries( $block );

		if ( $block_queries && isset( $block->innerHTML ) ) {
			$dom_handle = $this->get_dom_handler( $block );
			$dom        = $dom_handle->getDom( $block->innerHTML );
			$xpath      = new \DOMXPath( $dom );

			foreach ( $block_queries as $query ) {
				list( $query, ) = XPath::parse( $query );
				$elements       = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, ) = $dom_handle->getPartialInnerHTML( $element );
					$translation   = $this->getTranslation( $text, $lang, $block, $string_translations );
					$block         = $this->updateTranslationInBlock(
						$text,
						$this->apply_placeholders_for_html_entities( $translation ),
						$block,
						$element,
						$dom_handle
					);
				}
			}
			$content          = $dom_handle->getFullInnerHTML( $dom->documentElement );
			$block->innerHTML = $this->restore_placeholders_for_html_entities( reset( $content ) );

		} elseif ( isset( $block->blockName, $block->innerHTML ) && '' !== trim( $block->innerHTML ) ) {

			$translation = $this->getTranslation( $block->innerHTML, $lang, $block, $string_translations );

			if ( $translation ) {
				$block->innerHTML = $translation;
			}
		}

		return $block;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return null|string
	 */
	private function get_block_string_id( \WP_Block_Parser_Block $block ) {
		if ( isset( $block->blockName, $block->innerHTML ) && '' !== trim( $block->innerHTML ) ) {
			return $this->get_string_id( $block->blockName, $block->innerHTML );
		} else {
			return null;
		}
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array|null
	 */
	private function get_block_queries( \WP_Block_Parser_Block $block ) {
		return $this->get_block_config( $block, 'xpath' );
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return ListBlock|StandardBlock|HtmlBlock
	 */
	private function get_dom_handler( \WP_Block_Parser_Block $block ) {
		$class = wpml_collect(
			[
				self::LIST_BLOCK_NAME      => ListBlock::class,
				self::HTML_BLOCK_NAME      => HtmlBlock::class,
				self::LIST_ITEM_BLOCK_NAME => ListItemBlock::class,
			]
		)->get( $block->blockName, StandardBlock::class );

		return new $class();
	}

	/**
	 * @param string                 $text
	 * @param string                 $translation
	 * @param \WP_Block_Parser_Block $block
	 * @param \DOMNode               $element
	 * @param DOMHandle              $dom_handle
	 *
	 * @return \WP_Block_Parser_Block
	 */
	private function updateTranslationInBlock( $text, $translation, \WP_Block_Parser_Block $block, $element, $dom_handle ) {
		if ( $translation ) {
			$block = $dom_handle->applyStringTranslations( $block, $element, $translation, $text );
			$dom_handle->setElementValue( $element, $translation );
		}

		return $block;
	}

	private function getTranslation( $text, $lang, \WP_Block_Parser_Block $block, array $string_translations ) {
		$translationFromPageBuilder = apply_filters( 'wpml_pb_update_translations_in_content', $text, $lang );
		if ( $translationFromPageBuilder === $text ) {
			$string_id = $this->get_string_id( $block->blockName, $text );
			if ( (int) Obj::path( [ $string_id, $lang, 'status' ], $string_translations ) === ICL_TM_COMPLETE ) {
				return self::preserveNewLines( $text, $string_translations[ $string_id ][ $lang ]['value'] );
			} else {
				return null;
			}
		} else {
			return $translationFromPageBuilder;
		}
	}

	private static function preserveNewLines( $original, $translation ) {
		$endsWith = function ( $find, $s ) {
			return Str::sub( - Str::len( $find ), $s ) === $find; // @phpstan-ignore-line
		};

		if ( Str::startsWith( "\n", $original ) && ! Str::startsWith( "\n", $translation ) ) {
			$translation = "\n" . $translation;
		}

		if ( $endsWith( "\n", $original ) && ! $endsWith( "\n", $translation ) ) {
			$translation .= "\n";
		}

		return $translation;
	}

	/**
	 * HTML_ENTITY_PLACEHOLDERS
	 * Some translations are applied using \DomHandler, which converts any HTML entity
	 * back to it's character, i.e. &apos; becomes '.
	 * At some places (like shortcode attributes) it breaks the attribute value, because
	 * the delimter can use the same kind of quotes, i.e. [my attr='Some'value'] => broken.
	 * To avoid this problem the HTML entities are replaced before parsing the content with
	 * \DomDocument::loadHTML() and re-applied afterwards.
	 */
	const HTML_ENTITY_PLACEHOLDERS = [
		'&apos;' => 'WPML_PLACEHOLDER_APOS',
		'&quot;' => 'WPML_PLACEHOLDER_QUOT',
	];

	/**
	 * Replaces HTML entities with WPML entity placeholders in given $content.
	 * See self::HTML_ENTITY_PLACEHOLDERS for affected entities.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	private function apply_placeholders_for_html_entities( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		return str_replace(
			array_keys( self::HTML_ENTITY_PLACEHOLDERS ),
			array_values( self::HTML_ENTITY_PLACEHOLDERS ),
			$content
		);
	}

	/**
	 * Replaces WPML entity placeholders with HTML entities in given $content.
	 * See self::HTML_ENTITY_PLACEHOLDERS for affected entities.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	private function restore_placeholders_for_html_entities( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		return str_replace(
			array_values( self::HTML_ENTITY_PLACEHOLDERS ),
			array_keys( self::HTML_ENTITY_PLACEHOLDERS ),
			$content
		);
	}
}
