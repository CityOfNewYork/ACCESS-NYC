<?php

namespace WPML\Media\Duplication;

use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Post;
use WPML\LIB\WP\Hooks as WPHooks;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class Hooks {

	public static function add() {
		WPHooks::onAction( 'update_postmeta', 10, 4 )
			->then( spreadArgs( Fns::withoutRecursion( Fns::noop(), [ self::class, 'syncAttachedFile' ] ) ) );
	}

	public static function syncAttachedFile( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key === '_wp_attached_file' ) {

			$prev_value = Post::getMetaSingle( $object_id, $meta_key );

			// $isSameAsPrevious :: id → bool
			$isSameAsPrevious = pipe( Post::getMetaSingle( Fns::__, $meta_key ), Relation::equals( $prev_value ) );

			// $getPostsToUpdate :: id → [id]
			$getPostsToUpdate = pipe(
				PostTranslations::getIfOriginal(),
				Fns::reject( Obj::prop( 'original' ) ),
				Fns::map( Obj::prop( 'element_id' ) ),
				Fns::filter( $isSameAsPrevious )
			);

			Fns::each( Post::updateMeta( Fns::__, $meta_key, $meta_value ), $getPostsToUpdate( $object_id ) );
		}
	}
}
