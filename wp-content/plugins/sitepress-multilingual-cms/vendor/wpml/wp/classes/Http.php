<?php

namespace WPML\LIB\WP;


use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Either;
use WPML\FP\Fns;
use function WPML\FP\curryN;

/**
 * @method static callable|Either post( ...$url, ...$args ) - Curried :: string → array → Left( WP_Error ) | Right(string)
 */
class Http {

	use Macroable;

	public static function init() {
		self::macro( 'post', curryN( 2, function ( $url, $args ) {
			$response = Fns::make( \WP_Http::class )->post( $url, $args );

			return \is_wp_error( $response )
				? Either::Left( $response )
				: Either::Right( $response );
		} ) );
	}

}

Http::init();
