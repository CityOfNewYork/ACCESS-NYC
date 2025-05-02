<?php

namespace WPML\Utilities;

use WPML\LIB\WP\Hooks;

use function WPML\FP\spreadArgs;

class Labels implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_labelize_string' )
			->then( spreadArgs( [ $this, 'labelize' ] ) );
	}

	/**
	 * @param string|mixed $string
	 *
	 * @return string|mixed
	 */
	public static function labelize( $string ) {
		if ( ! is_string( $string ) ) {
			return $string;
		}

		$string = strtr(
			$string,
			[
				'-' => ' ',
				'_' => ' ',
			]
		);

		return preg_replace_callback(
			'/\b\p{L}/u',
			function ( $matches ) {
				return mb_convert_case( $matches[0], MB_CASE_UPPER, 'UTF-8' );
			},
			$string
		);
	}
}
