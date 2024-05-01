<?php

namespace WPML\PB\Cornerstone\Hooks;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class ShortcodeAttributes implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'shortcode_atts_cs_content', 10, 2 )
			->then( spreadArgs( [ self::class, 'restoreContentId' ] ) );
	}

	/**
	 * The `_p` key in $pairs is correctly set to the current (global) post,
	 * but it's overwritten with shortcode attribute ID copied from the original.
	 *
	 * @see \Themeco\Cornerstone\Services\FrontEnd::render_content()
	 *
	 * @param array $out
	 * @param array $pairs
	 *
	 * @return array
	 */
	public static function restoreContentId( $out, $pairs ) {
		if ( isset( $out['_p'], $pairs['_p'] ) ) {
			$out['_p'] = $pairs['_p'];
		}

		return $out;
	}
}
