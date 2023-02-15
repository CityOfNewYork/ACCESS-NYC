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

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'post', curryN( 2, function ( $url, $args ) {
			return WordPress::handleError( Fns::make( \WP_Http::class )->post( $url, $args ) );
		} ) );
	}

}

Http::init();
