<?php

namespace WPML\Posts;

use WPML\Collect\Support\Collection;
use WPML\DatabaseQueries\TranslatedPosts;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use function WPML\FP\partialRight;

class DeleteTranslatedContentOfLanguages {

	public function run( Collection $data ) {
		$deleteTranslatedContent = Fns::unary( partialRight( 'wp_delete_post', true ) );

		return Either::of( $data->get( 'language_code' ) )
		             ->filter( Lst::length() )
		             ->map( [ TranslatedPosts::class, 'getIdsForLangs' ] )
		             ->map( Fns::map( $deleteTranslatedContent ) )
		             ->coalesce( Fns::identity(), Fns::identity() );
	}
}