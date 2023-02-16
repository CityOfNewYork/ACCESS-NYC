<?php


namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Str;
use function WPML\FP\curryN;

/**
 * Class Url
 * @package WPML\LIB\WP
 *
 * @method static callable|mixed isAdmin( ...$url ) - Curried :: string → bool
 * @method static callable|mixed isLogin( ...$url ) - Curried :: string → bool
 * @method static callable|mixed isContentDirectory( ...$url ) - Curried :: string → bool
 */
class Url {
	use Macroable;
	
	/**
	 * @return bool
	 */
	public static function init() {
		self::macro( 'isLogin', curryN( 1, function ( $url ) {
			return Str::includes( wp_login_url(), $url );
		} ) );
		
		self::macro( 'isAdmin', curryN( 1, function ( $url ) {
			return Str::includes( admin_url(), $url );
		} ) );
		
		self::macro( 'isContentDirectory', curryN( 1, function ( $url ) {
			return Str::includes( WP_CONTENT_URL, $url );
		} ) );
	}
}

Url::init();