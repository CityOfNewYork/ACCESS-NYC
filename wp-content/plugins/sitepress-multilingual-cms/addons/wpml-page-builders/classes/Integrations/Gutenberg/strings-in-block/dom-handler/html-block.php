<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

use function WPML\FP\pipe;

class HtmlBlock extends StandardBlock {

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

		$cleanUp = pipe(
			'html_entity_decode',
			[ $this, 'removeCdataFromStyleTag' ],
			[ $this, 'removeCdataFromScriptTag' ]
		);

		return [
			$cleanUp( $innerHTML ),
			'AREA'
		];
	}
}
