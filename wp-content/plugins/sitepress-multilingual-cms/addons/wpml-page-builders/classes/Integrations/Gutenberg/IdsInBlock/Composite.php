<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

class Composite extends Base {

	/** @var Base[] $converters */
	private $converters;

	public function __construct( array $converters ) {
		$this->converters = $converters;
	}

	public function convert( array $block ) {
		foreach ( $this->converters as $converter ) {
			$block = $converter->convert( $block );
		}

		return $block;
	}
}
