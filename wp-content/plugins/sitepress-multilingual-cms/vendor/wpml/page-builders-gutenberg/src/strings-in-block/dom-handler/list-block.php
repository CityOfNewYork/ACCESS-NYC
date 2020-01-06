<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

class ListBlock extends DOMHandle {

	/**
	 * @param \DOMNode $element
	 * @param string   $context
	 *
	 * @return string
	 */
	protected function getInnerHTMLFromChildNodes( \DOMNode $element, $context ) {
		$innerHTML  = "";
		$is_partial = self::INNER_HTML_PARTIAL === $context;
		$children   = $element->childNodes;

		foreach ( $children as $child ) {
			if ( $is_partial && $this->isListNode( $child ) ) {
				continue;
			}

			$innerHTML .= $this->getAsHTML5( $child );
		}

		if ( $is_partial ) {
			$innerHTML = trim( $innerHTML );
		}

		return $innerHTML;
	}

	/**
	 * @param \DOMNode $clone
	 * @param \DOMNode $element
	 */
	protected function appendExtraChildNodes( \DOMNode $clone, \DOMNode $element ) {
		$child_list = $this->getChildList( $element );

		if ( $child_list ) {
			$clone->appendChild( $child_list );
		}
	}

	/**
	 * @param \DOMNode $node
	 *
	 * @return \DOMNode|null
	 */
	private function getChildList( \DOMNode $node ) {
		foreach ( $node->childNodes as $child_node ) {
			if ( $this->isListNode( $child_node ) ) {
				return $child_node;
			}
		}

		return null;
	}

	/**
	 * @param \DOMNode $node
	 *
	 * @return bool
	 */
	private function isListNode( \DOMNode $node ) {
		return isset( $node->tagName ) && in_array( $node->tagName, [ 'ul', 'ol' ], true );
	}
}
