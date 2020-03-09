<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class Integration implements \WPML\PB\Gutenberg\Integration {

	/** @var Translation $translation */
	private $translation;

	public function __construct( Translation $translation 	) {
		$this->translation = $translation;
	}

	public function add_hooks() {
		add_filter( 'render_block_data', [ $this, 'convertReusableBlock' ] );
	}

	/**
	 * Converts the block in the current language
	 *
	 * @param array $block
	 *
	 * @return array
	 */
	public function convertReusableBlock( array $block ) {
		return $this->translation->convertBlock( $block );
	}
}
