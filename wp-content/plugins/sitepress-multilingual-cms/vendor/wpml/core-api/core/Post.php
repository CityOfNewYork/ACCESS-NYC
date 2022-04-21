<?php


namespace WPML\Element\API;

use WPML\FP\Curryable;

/**
 * Class Post
 * @package WPML\Element\API
 *
 * @method static callable|string getLang( ...$postId ): Curried :: int->string
 */
class Post {

	use Curryable;

	public static function init() {

		self::curryN( 'getLang', 1, function ( $postId ) {
			/** @var \WPML_Admin_Post_Actions $wpml_post_translations */
			global $wpml_post_translations;

			return $wpml_post_translations->get_element_lang_code( $postId );
		} );

	}

}

Post::init();