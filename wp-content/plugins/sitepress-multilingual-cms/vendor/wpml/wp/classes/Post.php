<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Either;
use WPML\FP\Logic;
use function WPML\FP\curryN;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;

/**
 * Class Post
 * @package WPML\LIB\WP
 * @method callable|Either getTerms( ...$postId, ...$taxonomy )  - Curried:: int → string → Either false|WP_Error [WP_Term]
 * @method callable|mixed getMetaSingle( ...$postId, ...$key ) - Curried :: int → string → mixed
 * @method callable|int|bool updateMeta( ...$postId, ...$key, ...$value ) - Curried :: int → string → mixed → int|bool
 * @method callable|string|false getType( ...$postId ) - Curried :: int → string|bool
 */
class Post {

	use Macroable;

	public static function init() {

		self::macro( 'getTerms', curryN( 2, pipe(
			'get_the_terms',
			Logic::ifElse( Logic::isArray(), Either::class . '::right', Either::class . '::left' )
		) ) );

		self::macro( 'getMetaSingle', curryN( 2, partialRight( 'get_post_meta', true ) ) );

		self::macro( 'updateMeta', curryN( 3, 'update_post_meta' ) );

		self::macro( 'getType', curryN( 1, 'get_post_type' ) );

	}
}

Post::init();
