<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

use WPML\FP\Obj;

class Blocks {

	/**
	 * @param array $block
	 *
	 * @return bool
	 */
	public static function isReusable( array $block ) {
		return 'core/block' === $block['blockName']
		       && isset( $block['attrs']['ref'] )
		       && is_numeric( $block['attrs']['ref'] );
	}

	/**
	 * @param array $block
	 *
	 * @return int
	 */
	public static function getReusableId( array $block ) {
		return (int) Obj::path( [ 'attrs', 'ref' ], $block );
	}

	/**
	 * We get block IDs recursively to find possible
	 * nested reusable blocks.
	 * 
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function getChildrenIdsFromPost( $post_id ) {
		$post = get_post( $post_id );

		if ( $post ) {
			$blocks = \wpml_collect( \WPML_Gutenberg_Integration::parse_blocks( $post->post_content ) );
			return $blocks
				->filter( [ self::class, 'isReusable' ] )
				->map( function( $block ) {
					$block_id = self::getReusableId( $block );
					return array_merge( [ $block_id ], $this->getChildrenIdsFromPost( $block_id ) );
				})->flatten()->toArray();
		}

		return [];
	}
}
