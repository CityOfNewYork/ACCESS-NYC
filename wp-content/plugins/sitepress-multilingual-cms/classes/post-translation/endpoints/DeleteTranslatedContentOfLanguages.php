<?php

namespace WPML\Posts;

use WPML\Collect\Support\Collection;
use WPML\DatabaseQueries\TranslatedPosts;
use WPML\DatabaseQueries\TranslatedTerms;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\Element\API\Languages;

class DeleteTranslatedContentOfLanguages {

	public function run( Collection $data ) {
		$activeLangs = array_keys( Languages::getActive() );
		$isInactiveLang = function ( $code ) use ( $activeLangs ) {
			return ! in_array( $code, $activeLangs );
		};

		return Either::of( $data->get( 'language_code' ) )
		             ->map( Fns::filter( $isInactiveLang ) )
		             ->filter( Lst::length() )
		             ->map( [self::class, 'deleteTerms'] )
		             ->map( [self::class, 'deletePosts'] )
		             ->coalesce( Fns::identity(), Fns::identity() );
	}

	/**
	 * @param array $langCodes
	 *
	 * @return callable|\WPML\FP\Left|\WPML\FP\Right
	 */
	public static function deletePosts( $langCodes ) {
		$postIds = TranslatedPosts::getIdsForLangs( $langCodes );

		foreach ( $postIds as $id ) {
			if ( ! \wp_delete_post( $id, true ) ) {
				return Either::left( 'Can not delete post with ID: ' . $id );
			}
		}

		return Either::of( true );
	}

	/**
	 * @param array $langCodes
	 *
	 * @return array
	 */
	public static function deleteTerms( $langCodes ) {
		global $sitepress;

		$termsData = TranslatedTerms::getIdsForLangs( $langCodes );

		// Remove WPML filter to prevent conversion of Ids.
		$argsFilterRemoved = remove_filter( 'get_terms_args', [ $sitepress, 'get_terms_args_filter' ] );
		$getTermsFilterRemoved = remove_filter( 'get_term', [ $sitepress, 'get_term_adjust_id' ], 1 );
		$clausesFilterRemoved = remove_filter( 'terms_clauses', [ $sitepress, 'terms_clauses' ] );

		if ( $argsFilterRemoved && $getTermsFilterRemoved && $clausesFilterRemoved ) {
			foreach ( $termsData as $termData ) {
				\wp_delete_term( $termData->term_id, $termData->taxonomy );
			}

			// Add terms filters again - required for test.
			add_filter( 'get_terms_args', [ $sitepress, 'get_terms_args_filter' ], 10, 2 );
			add_filter( 'get_term', [ $sitepress, 'get_term_adjust_id' ], 1, 1 );
			add_filter( 'terms_clauses', [ $sitepress, 'terms_clauses' ], 10, 3 );
		}

		return $langCodes;
	}
}
