<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

class StandardBlock extends DOMHandle {

	/**
	 * @param \DOMNode $element
	 * @param string   $context
	 *
	 * @return string
	 */
	protected function getInnerHTMLFromChildNodes( \DOMNode $element, $context ) {
		$innerHTML = "";
		$children  = $element->childNodes;

		foreach ( $children as $child ) {
			$innerHTML .= $this->getAsHTML5( $child );
		}

		return $innerHTML;
	}

	/**
	 * @param \DOMNode $clone
	 * @param \DOMNode $element
	 */
	protected function appendExtraChildNodes( \DOMNode $clone, \DOMNode $element ) {

	}
}
