<?php

namespace WPML\PB\Gutenberg\Navigation;

use WPML\FP\Obj;
use WPML\FP\Relation;

class Frontend implements \WPML\PB\Gutenberg\Integration {

	public function add_hooks() {
		add_filter( 'render_block_data', [ $this, 'translateNavigationId' ] );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function translateNavigationId( $data ) {
		if ( Relation::propEq( 'blockName', 'core/navigation', $data ) && Obj::path( [ 'attrs', 'ref' ], $data ) ) {
			$data['attrs']['ref'] = apply_filters( 'wpml_object_id', $data['attrs']['ref'], 'wp_navigation', true );
		}

		return $data;
	}

}
