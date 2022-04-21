<?php

namespace WPML\Media;

use WPML\Element\API\PostTranslations;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class FrontendHooks implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wp_get_attachment_caption', 10, 2 )
			->then( spreadArgs( Fns::withoutRecursion(  Fns::identity(), [ __CLASS__, 'translateCaption' ] ) ) );
	}

	/**
	 * @param string $caption
	 * @param int    $postId
	 *
	 * @return string
	 */
	public static function translateCaption( $caption, $postId ) {
		// $convertId :: int -> string|int|null
		$convertId = pipe( PostTranslations::getInCurrentLanguage(), Obj::prop( 'element_id' ) );

		return Maybe::of( $postId )
		            ->map( $convertId )
		            ->map( Cast::toInt() )
		            ->reject( Relation::equals( $postId ) )
		            ->map( 'wp_get_attachment_caption' )
		            ->getOrElse( $caption );
	}
}
