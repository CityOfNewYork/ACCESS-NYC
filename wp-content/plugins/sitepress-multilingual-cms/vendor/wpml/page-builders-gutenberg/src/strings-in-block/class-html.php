<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\StandardBlock;
use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\ListBlock;
use WPML\PB\Gutenberg\XPath;

class HTML extends Base {

	const LIST_BLOCK_NAME = 'core/list';

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

			foreach ( $block_queries as $query ) {
				list( $query, $definedType ) = XPath::parse( $query );
				$elements = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, $type ) = $dom_handle->getPartialInnerHTML( $element );
					if ( $text ) {
						$string_id = $this->get_string_id( $block->blockName, $text );
						$strings[] = $this->build_string( $string_id, $block->blockName, $text, $definedType ? $definedType : $type );
					}
				}
			}

		} else {

			$string_id = $this->get_block_string_id( $block );
			if ( $string_id ) {
				$strings[] = $this->build_string( $string_id, $block->blockName, $block->innerHTML, 'VISUAL' );
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
				$elements = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, ) = $dom_handle->getPartialInnerHTML( $element );
					$string_id = $this->get_string_id( $block->blockName, $text );
					if (
						isset( $string_translations[ $string_id ][ $lang ] ) &&
						ICL_TM_COMPLETE == $string_translations[ $string_id ][ $lang ]['status']
					) {
						$translation = $string_translations[ $string_id ][ $lang ]['value'];
						$block       = self::update_string_in_innerContent( $block, $element, $translation );
						$dom_handle->setElementValue( $element, $translation );
					}
				}
			}
			list( $block->innerHTML, ) = $dom_handle->getFullInnerHTML( $dom->documentElement );

		} else {

			$string_id = $this->get_block_string_id( $block );
			if (
				isset( $string_translations[ $string_id ][ $lang ] ) &&
				ICL_TM_COMPLETE == $string_translations[ $string_id ][ $lang ]['status']
			) {
				$block->innerHTML = $string_translations[ $string_id ][ $lang ]['value'];
			}

		}

		return $block;
	}

	/**
	 * This is required when a block has innerBlocks and translatable content at the root.
	 * Unfortunately we cannot use the DOM because we have only HTML extracts which
	 * are not valid taken independently.
	 *
	 * e.g. {
	 *          innerContent => [
	 *              '<div><p>The title</p>',
	 *              null,
	 *              '\n\n',
	 *              null,
	 *              '</div>'
	 *          ]
	 *      }
	 *
	 * @param \WP_Block_Parser_Block $block
	 * @param \DOMNode               $element
	 * @param string                 $translation
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public static function update_string_in_innerContent( \WP_Block_Parser_Block $block, \DOMNode $element, $translation ) {
		if ( empty( $block->innerContent ) ) {
			return $block;
		}

		$search_value = preg_quote( $element->nodeValue, '/' );

		if ( $element instanceof \DOMAttr ) {
			$search = '/(")(' . $search_value . ')(")/';
		} else {
			$search = '/(>)(' . $search_value . ')(<)/';
		}

		foreach ( $block->innerContent as &$inner_content ) {
			if ( $inner_content ) {
				$inner_content = preg_replace( $search, '${1}' . $translation . '${3}', $inner_content );
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
	 * @return ListBlock|StandardBlock
	 */
	private function get_dom_handler( \WP_Block_Parser_Block $block ) {
		if ( self::LIST_BLOCK_NAME === $block->blockName ) {
			return new ListBlock();
		}

		return new StandardBlock();
	}
}
