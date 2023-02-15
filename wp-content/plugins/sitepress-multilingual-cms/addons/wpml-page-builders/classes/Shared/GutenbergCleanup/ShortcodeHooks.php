<?php

namespace WPML\PB\GutenbergCleanup;

use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\LIB\WP\Gutenberg;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;
use function WPML\FP\tap as tap;

class ShortcodeHooks implements \IWPML_Backend_Action {

	public function add_hooks() {
		add_action(
			'wp_insert_post',
			Fns::withoutRecursion( Fns::noop(), [ $this, 'removeGutenbergFootprint' ] ),
			10, 2
		);
	}

	/**
	 * @param int            $post_ID
	 * @param \WP_Post|mixed $post
	 */
	public function removeGutenbergFootprint( $post_ID, $post ) {
		// $isWpPost :: mixed -> bool (wpmlcore-8575)
		$isWpPost = partialRight( 'is_a', \WP_Post::class );

		// $isBuiltWithShortcodes :: \WP_Post -> bool
		$isBuiltWithShortcodes = function( \WP_Post $post ) {
			/**
			 * @since WPML 4.4.9
			 *
			 * @param bool     false
			 * @param \WP_Post $post
			 */
			return apply_filters( 'wpml_pb_is_post_built_with_shortcodes', false, $post );
		};

		// $hasGutenbergMetaData :: \WP_Post -> bool
		$hasGutenbergMetaData = pipe(
			Obj::prop( 'post_content' ),
			Gutenberg::hasBlock()
		);

		// $removeHtmlComments :: \WP_Post -> \WP_Post
		$removeHtmlComments = tap( pipe(
			Obj::over( Obj::lensProp( 'post_content' ), Gutenberg::stripBlockData() ),
			'wp_update_post'
		) );

		// $deleteGutenbergPackage :: \WP_Post -> void
		$deleteGutenbergPackage = pipe(
			Obj::prop( 'ID' ),
			[ Package::class, 'get' ],
			[ Package::class, 'delete' ]
		);

		Maybe::of( $post )
			->filter( $isWpPost )
			->filter( $isBuiltWithShortcodes )
			->filter( $hasGutenbergMetaData )
			->map( $removeHtmlComments )
			->map( $deleteGutenbergPackage );
	}
}
