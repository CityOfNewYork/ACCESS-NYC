<?php

namespace WPML\Media\Duplication;

use WPML\Element\API\IfOriginalPost;
use WPML\FP\Fns;
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

			$prevValue = Post::getMetaSingle( $object_id, $meta_key );

			// $isMetaSameAsPrevious :: id → bool
			$isMetaSameAsPrevious = pipe( Post::getMetaSingle( Fns::__, $meta_key ), Relation::equals( $prevValue ) );

			IfOriginalPost::getTranslationIds( $object_id )
						  ->filter( Fns::unary( $isMetaSameAsPrevious ) )
						  ->each( Fns::unary( Post::updateMeta( Fns::__, $meta_key, $meta_value ) ) );
		}
	}
}
