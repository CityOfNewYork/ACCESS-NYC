<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

use WPML\PB\Gutenberg\StringsInBlock\Base;
use function WPML\FP\pipe;

abstract class DOMHandle {

	const INNER_HTML_PARTIAL = 'partial';
	const INNER_HTML_FULL    = 'full';

	/**
	 * @param string $html
	 *
	 * @return \DOMXPath
	 */
	public function getDomxpath( $html ) {
		$dom = $this->getDom( $html );

		return new \DOMXPath( $dom );
	}

	/**
	 * @param string $html
	 *
	 * @return \DOMDocument
	 */
	public function getDom( $html ) {
		$dom = new \DOMDocument();
		\libxml_use_internal_errors( true );
		$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );
		$dom->loadHTML( '<div>' . $html . '</div>' );
		\libxml_clear_errors();

		// Remove doc type and <html> <body> wrappers
		$dom->removeChild( $dom->doctype );

		/**
		 * $dom->firstChild->firstChild->firstChild is node that we are intersted in (without body tags).
		 * $dom->firstChild Old node that we are replacing
		 */
		$dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild );
		return $dom;
	}

	/**
	 * This is required when a block has innerBlocks and translatable content at the root.
	 * Unfortunately we cannot use the DOM because we have only HTML extracts which
	 * are not valid taken independently.
	 *
	 * {@internal
	 *          innerContent => [
	 *              '<div><p>The title</p>',
	 *              null,
	 *              '\n\n',
	 *              null,
	 *              '</div>'
	 *          ]}
	 *
	 * @param \WP_Block_Parser_Block $block
	 * @param \DOMNode               $element
	 * @param string                 $translation
	 * @param string|null            $originalValue
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function applyStringTranslations( \WP_Block_Parser_Block $block, \DOMNode $element, $translation, $originalValue = null ) {
		if ( empty( $block->innerContent ) || empty( $element->nodeValue ) ) {
			return $block;
		}

		if ( $element instanceof \DOMAttr ) {
			$search_value = preg_quote( esc_attr( $element->nodeValue ), '/' );
			$search       = '/(")(' . $search_value . ')(")/';
			$translation  = esc_attr( $translation );
		} else {
			$replace_full_html_node_content = $element->childNodes->length > 0 && $originalValue;

			$search_value = preg_quote( $replace_full_html_node_content ? $originalValue : $element->nodeValue, '/' );
			$search       = '/(>)(' . $search_value . ')(<)/';
		}

		foreach ( $block->innerContent as &$inner_content ) {
			if ( $inner_content ) {
				$inner_content = preg_replace( $search, '${1}' . $translation . '${3}', $inner_content );
			}
		}

		return $block;
	}

	/**
	 * @param \DOMNode $element
	 * @param string   $context
	 *
	 * @return array
	 */
	protected function getInnerHTML( \DOMNode $element, $context ) {
		$innerHTML = $element instanceof \DOMText
			? $element->nodeValue
			: $this->getInnerHTMLFromChildNodes( $element, $context );

		$type = Base::get_string_type( $innerHTML );

		if ( 'VISUAL' !== $type ) {
			$innerHTML = html_entity_decode( $innerHTML );
		}

		$removeCdata = pipe(
			[ $this, 'removeCdataFromStyleTag' ],
			[ $this, 'removeCdataFromScriptTag' ]
		);

		return [ $removeCdata($innerHTML), $type ];
	}

	/**
	 * @param \DOMNode $element
	 * @param string   $context
	 *
	 * @return string
	 */
	abstract protected function getInnerHTMLFromChildNodes( \DOMNode $element, $context );

	/**
	 * @param \DOMNode $element
	 *
	 * @return array
	 */
	public function getPartialInnerHTML( \DOMNode $element ) {
		return $this->getInnerHTML( $element, self::INNER_HTML_PARTIAL );
	}

	/**
	 * @param \DOMNode $element
	 *
	 * @return array
	 */
	public function getFullInnerHTML( \DOMNode $element ) {
		return $this->getInnerHTML( $element, self::INNER_HTML_FULL );
	}

	/**
	 * @param \DOMNode $element
	 * @param string   $value
	 */
	public function setElementValue( \DOMNode $element, $value ) {
		if ( $element instanceof \DOMAttr ) {
			// @phpstan-ignore-next-line
			$element->parentNode->setAttribute( $element->name, $value );
		} elseif ( $element instanceof \DOMText ) {
			$clone = $this->cloneNodeWithoutChildren( $element );
			$clone->nodeValue = $value;
			$element->parentNode->replaceChild( $clone, $element );
		} else {
			$clone = $this->cloneNodeWithoutChildren( $element );
			$fragment = $this->getDom( $value )->firstChild; // Skip the wrapping div
			foreach ( $fragment->childNodes as $child ) {
				$clone->appendChild( $element->ownerDocument->importNode( $child, true ) );
			}

			$this->appendExtraChildNodes( $clone, $element );

			$element->parentNode->replaceChild( $clone, $element );
		}
	}

	/**
	 * @param \DOMNode $clone
	 * @param \DOMNode $element
	 */
	abstract protected function appendExtraChildNodes( \DOMNode $clone, \DOMNode $element );

	/**
	 * @param \DOMNode $element
	 *
	 * @return \DOMNode
	 */
	private function cloneNodeWithoutChildren( \DOMNode $element ) {
		return $element->cloneNode( false );
	}

	protected function getAsHTML5( \DOMNode $element ) {
		return str_replace( '--/>', '-->', strtr(
			$element->ownerDocument->saveXML( $element, LIBXML_NOEMPTYTAG ),
			[
				'></area>'   => '/>',
				'></base>'   => '/>',
				'></br>'     => '/>',
				'></col>'    => '/>',
				'></embed>'  => '/>',
				'></hr>'     => '/>',
				'></img>'    => '/>',
				'></input>'  => '/>',
				'></link>'   => '/>',
				'></meta>'   => '/>',
				'></param>'  => '/>',
				'></source>' => '/>',
				'></track>'  => '/>',
				'></wbr>'    => '/>',
			] ) );
	}

	public static function removeCdataFromStyleTag( $innerHTML ) {
		return preg_replace( '/<style(.*?)><!\\[CDATA\\[(.*?)\\]\\]><\\/style>/s', '<style$1>$2</style>', $innerHTML );
	}

	public static function removeCdataFromScriptTag( $innerHTML ) {
		return preg_replace( '/<script(.*?)><!\\[CDATA\\[(.*?)\\]\\]><\\/script>/s', '<script$1>$2</script>', $innerHTML );
	}

}
