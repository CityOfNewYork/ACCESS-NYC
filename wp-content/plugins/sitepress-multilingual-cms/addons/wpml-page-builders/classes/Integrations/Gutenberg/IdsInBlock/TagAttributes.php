<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\PB\Gutenberg\StringsInBlock\DOMHandler\StandardBlock;
use WPML\PB\Gutenberg\StringsInBlock\HTML;

class TagAttributes extends Base {

	/** @var array $attributesToConvert */
	private $attributesToConvert;

	public function __construct( array $attributesToConvert ) {
		$this->attributesToConvert = $attributesToConvert;
	}

	public function convert( array $block ) {
		$domHandler = new StandardBlock();
		$dom        = $domHandler->getDom( $block['innerHTML'] );
		$xpath      = new \DOMXPath( $dom );

		foreach ( $this->attributesToConvert as $attributeConfig ) {
			$getConfig = Obj::prop( Fns::__, $attributeConfig );
			$nodes     = $xpath->query( $getConfig( 'xpath' ) );

			if ( ! $nodes ) {
				continue;
			}

			foreach ( $nodes as $node ) {
				/** @var \DOMNode $node */
				$ids = self::convertIds( $node->nodeValue, $getConfig( 'slug' ), $getConfig( 'type' ) );
				$blockObject = \WPML_Gutenberg_Integration::sanitize_block( $block );
				$block = (array) $domHandler->applyStringTranslations( $blockObject, $node, $ids, null );
				$domHandler->setElementValue( $node, $ids );
			}
		}

		list( $block['innerHTML'], ) = $domHandler->getFullInnerHTML( $dom->documentElement );

		return $block;
	}
}
