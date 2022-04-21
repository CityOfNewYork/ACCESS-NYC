<?php

namespace WPML\TM\ATE\Review;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;

/**
 * This will allow displaying private CPT reviews on the frontend.
 */
class NonPublicCPTPreview {

	const POST_TYPE = 'wpmlReviewPostType';

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function addArgs( array $args ) {
		return Obj::assoc( self::POST_TYPE, \get_post_type( $args['preview_id'] ), $args );
	}

	/**
	 * @return callable
	 */
	public static function allowReviewPostTypeQueryVar() {
		return Lst::append( self::POST_TYPE );
	}

	/**
	 * @return callable
	 */
	public static function enforceReviewPostTypeIfSet() {
		return Logic::ifElse(
			Obj::prop( self::POST_TYPE ),
			Obj::renameProp( self::POST_TYPE, 'post_type' ),
			Fns::identity()
		) ;
	}
}
