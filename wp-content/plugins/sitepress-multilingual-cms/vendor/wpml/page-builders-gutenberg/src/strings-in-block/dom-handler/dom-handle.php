<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

use WPML\PB\Gutenberg\StringsInBlock\Base;

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
	 * @param \DOMNode $element
	 * @param string   $context
	 *
	 * @return array
	 */
	private function getInnerHTML( \DOMNode $element, $context ) {
		$innerHTML = $element instanceof \DOMText
			? $element->nodeValue
			: $this->getInnerHTMLFromChildNodes( $element, $context );

		$type = Base::get_string_type( $innerHTML );

		if ( 'VISUAL' !== $type ) {
			$innerHTML = html_entity_decode( $innerHTML );
		}

		$innerHTML = $this->removeCdataFromStyleTag( $innerHTML );

		return array( $innerHTML, $type );
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
		return strtr(
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
			] );
	}

	private function removeCdataFromStyleTag( $innerHTML ) {
		return preg_replace( '/<style(.*?)><!\\[CDATA\\[(.*?)\\]\\]><\\/style>/', '<style$1>$2</style>', $innerHTML );
	}

}
