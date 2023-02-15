<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Fns;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class WooCommerce implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'option_elementor_woocommerce_purchase_summary_page_id' )
			->then( spreadArgs( Fns::memorize( function( $pageId ) {
				return apply_filters( 'wpml_object_id', $pageId, 'page', true );
			} ) ) );
	}
}
