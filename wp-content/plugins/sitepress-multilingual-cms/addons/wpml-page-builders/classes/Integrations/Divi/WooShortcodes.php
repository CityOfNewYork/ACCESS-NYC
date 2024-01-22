<?php

namespace WPML\Compatibility\Divi;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;
use WPML\FP\Obj;

class WooShortcodes implements \IWPML_Frontend_Action {

	const WOO_SHORTCODES = [
		'et_pb_wc_description',
		'et_pb_wc_title',
	];

	public function add_hooks() {
		Hooks::onFilter( 'et_pb_module_shortcode_attributes', 10, 3 )
			->then( spreadArgs( [ $this, 'translateAttributes' ] ) );
	}

	/**
	 * @param array  $shortcodeAttrs
	 * @param array  $attrs
	 * @param string $slug
	 *
	 * @return array
	 */
	public function translateAttributes( $shortcodeAttrs, $attrs, $slug ) {
		if ( in_array( $slug, self::WOO_SHORTCODES, true ) && (int) Obj::prop( 'product', $shortcodeAttrs ) ) {
			$shortcodeAttrs['product'] = apply_filters( 'wpml_object_id', $shortcodeAttrs['product'], 'product', true );
		}
		return $shortcodeAttrs;
	}
}
