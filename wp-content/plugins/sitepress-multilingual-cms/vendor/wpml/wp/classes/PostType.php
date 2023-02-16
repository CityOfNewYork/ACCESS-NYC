<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Fns;
use WPML\FP\Just;
use WPML\FP\Nothing;
use WPML\FP\Maybe;
use function WPML\FP\curryN;
use WPML\FP\Obj;
use function WPML\FP\pipe;

/**
 * Class PostType
 * @package WPML\LIB\WP
 * @method static callable|int getPublishedCount( ...$postType ) - Curried :: string → int
 * @method static callable|Just|Nothing getObject( ...$postType ) - Curried :: string → Maybe( WP_Post_Type )|Nothing
 * @method static callable|Just|Nothing getPluralName( ...$postType ) - Curried :: string → Maybe(string) |Nothing
 * @method static callable|Just|Nothing getSingularName( ...$postType ) - Curried :: string → Maybe(string) |Nothing
 */
class PostType {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'getPublishedCount', curryN( 1, pipe( 'wp_count_posts', Obj::propOr( 0, 'publish' ) ) ) );

		self::macro( 'getObject', curryN( 1, pipe( Maybe::of(), Fns::map( 'get_post_type_object' ) ) ) );
		self::macro( 'getPluralName', curryN( 1, pipe( self::getObject(), Fns::map( Obj::path( [ 'labels', 'name' ] ) ) ) ) );
		self::macro( 'getSingularName', curryN( 1, pipe( self::getObject(), Fns::map( Obj::path( [ 'labels', 'singular_name' ] ) ) ) ) );
	}
}

PostType::init();
