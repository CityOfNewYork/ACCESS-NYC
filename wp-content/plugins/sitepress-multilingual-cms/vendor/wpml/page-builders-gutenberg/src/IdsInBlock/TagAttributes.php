<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\StandardBlock;
use WPML\PB\Gutenberg\StringsInBlock\HTML;

class TagAttributes extends Base {

	private $attributesToConvert;

	public function __construct( array $attributesToConvert ) {
		$this->attributesToConvert = $attributesToConvert;
	}

	public function convert( array $block ) {
		$domHandler = new StandardBlock();
		$dom        = $domHandler->getDom( $block['innerHTML'] );
		$xpath      = new \DOMXPath( $dom );

		foreach ( $this->attributesToConvert as $attributeConfig ) {
			$nodes = $xpath->query( $attributeConfig['xpath'] );

			if ( ! $nodes ) {
				continue;
			}

			foreach ( $nodes as $node ) {
				/** @var \DOMNode $node */
				$ids = implode( ',' , self::convertIds( explode( ',', $node->nodeValue ), $attributeConfig['slug'], $attributeConfig['type'] ) );
				$blockObject = \WPML_Gutenberg_Integration::sanitize_block( $block );
				$block = (array) HTML::update_string_in_innerContent( $blockObject, $node, $ids );
				$domHandler->setElementValue( $node, $ids );
			}
		}

		list( $block['innerHTML'], ) = $domHandler->getFullInnerHTML( $dom->documentElement );

		return $block;
	}
}
