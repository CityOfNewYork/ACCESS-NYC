<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

use WPML\FP\Fns;

class Integration implements \WPML\PB\Gutenberg\Integration {

	/** @var Translation $translation */
	private $translation;

	public function __construct( Translation $translation 	) {
		$this->translation = $translation;
	}

	public function add_hooks() {
		add_filter( 'render_block_data', [ $this, 'convertReusableBlock' ] );
		add_filter( 'render_block', Fns::withoutRecursion( Fns::identity(), [ $this, 'reRenderInnerReusableBlock' ] ), 10, 2 );
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

	/**
	 * The filter hook `render_block_data` applies only for root blocks,
	 * nested blocks are not passing through this hook.
	 * That's why we need to re-render reusable nested blocks.
	 *
	 * @param string $blockContent
	 * @param array  $block
	 *
	 * @return string
	 */
	public function reRenderInnerReusableBlock( $blockContent, $block ) {
		$originalId = Blocks::getReusableId( $block );

		if ( $originalId ) {
			$convertedBlock = $this->translation->convertBlock( $block );

			if ( Blocks::getReusableId( $convertedBlock ) !== $originalId ) {
				return render_block( $convertedBlock );
			}
		}

		return $blockContent;
	}
}
