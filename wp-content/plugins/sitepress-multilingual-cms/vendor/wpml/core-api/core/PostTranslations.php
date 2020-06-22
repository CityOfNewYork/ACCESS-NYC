<?php

namespace WPML\Element\API;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Lst;
use WPML\LIB\WP\Post;
use function WPML\FP\curryN;

/**
 * Class PostTranslations
 * @package WPML\Element\API
 * @method static callable|int setAsSource( ...$el_id, ...$language_code ) - Curried :: int → string → void
 * @method static callable|int setAsTranslationOf( ...$el_id, ...$translated_id, ...$language_code )
 * @method static callable|array get( ...$el_id ) - Curried :: int → [object]
 * @method static callable|array getIfOriginal( ...$el_id ) - Curried :: int → [object]
 */
class PostTranslations {

	use Macroable;

	/**
	 * @ignore
	 */
	public static function init() {

		self::macro( 'setAsSource', curryN( 2, self::withPostType( Translations::setAsSource() ) ) );

		self::macro( 'setAsTranslationOf', curryN( 3, self::withPostType( Translations::setAsTranslationOf() ) ) );

		self::macro( 'get', curryN( 1, self::withPostType( Translations::get() ) ) );

		self::macro( 'getIfOriginal', curryN( 1, self::withPostType( Translations::getIfOriginal() ) ) );

	}

	/**
	 * @ignore
	 * @param $fn
	 *
	 * @return \Closure
	 */
	public static function withPostType( $fn ) {
		return function () use ( $fn ) {
			$args = func_get_args();

			return call_user_func_array( $fn, Lst::insert( 1, 'post_' . Post::getType( $args[0] ), $args ) );
		};
	}
}

PostTranslations::init();
