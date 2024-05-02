<?php

namespace WPML\TM\TranslationDashboard\Endpoints;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use function WPML\FP\spreadArgs;

/**
 * It duplicates posts into specified languages.
 */
class Duplicate {

	public function run( Collection $data ) {
		global $sitepress;

		$postIds  = $data->get( 'postIds' );
		$languages = $data->get( 'languages' );

		$pairs  = Lst::xprod( $postIds, $languages );
		$result = Fns::map( spreadArgs( function ( $postId, $languageCode ) use ( $sitepress ) {
			return [ $postId, $languageCode, $sitepress->make_duplicate( $postId, $languageCode ) ];
		} ), $pairs );

		return Either::of( $result );
	}
}
