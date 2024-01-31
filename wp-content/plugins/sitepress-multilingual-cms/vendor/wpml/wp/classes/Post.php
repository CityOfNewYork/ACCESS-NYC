<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use function WPML\FP\curryN;
use function WPML\FP\gatherArgs;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;

/**
 * Class Post
 * @package WPML\LIB\WP
 * @method static callable|Either getTerms( ...$postId, ...$taxonomy )  - Curried:: int → string → Either false|WP_Error [WP_Term]
 * @method static callable|mixed getMetaSingle( ...$postId, ...$key ) - Curried :: int → string → mixed
 * @method static callable|int|bool updateMeta( ...$postId, ...$key, ...$value ) - Curried :: int → string → mixed → int|bool
 * @method static callable|bool deleteMeta( ...$postId, ...$key ) - Curried :: int → string → bool
 * @method static callable|string|false getType( ...$postId ) - Curried :: int → string|bool
 * @method static callable|\WP_Post|null get( ...$postId ) - Curried :: int → \WP_Post|null
 * @method static callable|string|false getStatus( ...$postId ) - Curried :: int → string|bool
 * @method static callable|int update(...$data) - Curried :: array -> int
 * @method static callable|int insert(...$data) - Curried :: array -> int
 * @method static callable|int setStatus(...$id, ...$status) - Curried :: int -> string -> int
 * @method static callable|int setStatusWithoutFilters(...$id, ...$status) - Curried :: int -> string -> int
 * @method static callable|\WP_Post|false|null delete(...$id) - Curried :: int -> \WP_Post|false|null
 * @method static callable|\WP_Post|false|null trash(...$id) - Curried :: int -> \WP_Post|false|null
 */
class Post {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {

		self::macro( 'getTerms', curryN( 2, pipe(
			'get_the_terms',
			Logic::ifElse( Logic::isArray(), [ Either::class, 'right' ], [ Either::class, 'left' ] )
		) ) );

		self::macro( 'getMetaSingle', curryN( 2, partialRight( 'get_post_meta', true ) ) );

		self::macro( 'updateMeta', curryN( 3, 'update_post_meta' ) );

		self::macro( 'deleteMeta', curryN( 2, 'delete_post_meta' ) );

		self::macro( 'getType', curryN( 1, 'get_post_type' ) );

		self::macro( 'get', curryN( 1, Fns::unary( 'get_post' ) ) );

		self::macro( 'getStatus', curryN( 1, 'get_post_status' ) );

		self::macro( 'update', curryN( 1, Fns::unary( 'wp_update_post' ) ) );

		self::macro( 'insert', curryN( 1, Fns::unary( 'wp_insert_post' ) ) );

		self::macro( 'setStatus', curryN(2, gatherArgs( pipe( Lst::zipObj( [ 'ID', 'post_status' ] ), self::update() ) ) ) );

		self::macro( 'setStatusWithoutFilters', curryN( 2, function ( $id, $newStatus ) {
			global $wpdb;
			$result = $wpdb->update( $wpdb->posts, [ 'post_status' => $newStatus ], [ 'ID' => $id ] ) ? $id : 0;
			if ( $result ) clean_post_cache( $id );
			return $result;
;		} ) );

		self::macro( 'delete', curryN( 1, partialRight( 'wp_delete_post', true ) ) );

		self::macro( 'trash', curryN( 1, partialRight( 'wp_delete_post', false ) ) );
	}
}

Post::init();
