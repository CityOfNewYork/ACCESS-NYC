<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

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
			return $blocks->filter( function( $block ) {
				return 'core/block' === $block['blockName']
				       && isset( $block['attrs']['ref'] )
				       && is_numeric( $block['attrs']['ref'] );
			})->map( function( $block ) {
				$block_id = (int) $block['attrs']['ref'];
				return array_merge( [ $block_id ], $this->getChildrenIdsFromPost( $block_id ) );
			})->flatten()->toArray();
		}

		return [];
	}
}
