<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

class BlockAttributes extends Base {

	private $attributesToConvert;

	public function __construct( array $attributesToConvert ) {
		$this->attributesToConvert = $attributesToConvert;
	}

	public function convert( array $block ) {
		foreach ( $this->attributesToConvert as $attributeConfig ) {
			$name = $attributeConfig['name'];

			if ( isset( $block['attrs'][ $name ] ) ) {
				$block['attrs'][ $name ] = self::convertIds(
					$block['attrs'][ $name ],
					$attributeConfig['slug'],
					$attributeConfig['type']
				);
			}
		}

		return $block;
	}
}
