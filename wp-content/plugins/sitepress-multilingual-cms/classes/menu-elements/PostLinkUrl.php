<?php

namespace WPML\TM\Menu;

use SitePress;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\User;
use WPML\LIB\WP\Post;
use function WPML\FP\pipe;

class PostLinkUrl {
	/**
	 * @param int $postId
	 *
	 * @return string
	 */
	public function viewLinkUrl( $postId ) {
		return Maybe::of( $postId )
		            ->map( Post::get() )
		            ->reject( pipe( Obj::prop( 'post_status' ), Lst::includes( Fns::__, [
			            'private',
			            'trash',
		            ] ) ) )
		            ->map( 'get_permalink' )
		            ->getOrElse( '' );
	}
}