<?php

namespace WPML\Posts;

use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\LIB\WP\PostType;

class UntranslatedCount {

	public function run( Collection $data, \wpdb $wpdb ) {
		$postTypes = $data->get( 'postTypes', PostTypes::getAutomaticTranslatable() );

		$postIn   = wpml_prepare_in( Fns::map( Str::concat( 'post_' ), $postTypes ) );
		$statuses = wpml_prepare_in( [ ICL_TM_NOT_TRANSLATED, ICL_TM_ATE_CANCELLED ] );

		$query = "
			SELECT translations.post_type, COUNT(translations.ID)
			FROM (
	            SELECT RIGHT(element_type, LENGTH(element_type) - 5) as post_type, posts.ID
	            FROM {$wpdb->prefix}icl_translations
	            INNER JOIN {$wpdb->prefix}posts posts ON element_id = ID
	            WHERE element_type IN ({$postIn})
		           AND post_status = 'publish'
		           AND source_language_code IS NULL
		           AND language_code = %s
		           AND (
	                   SELECT COUNT(trid)
	                   FROM {$wpdb->prefix}icl_translations icl_translations_inner
	                   INNER JOIN {$wpdb->prefix}icl_translation_status icl_translations_status
	                                       on icl_translations_inner.translation_id = icl_translations_status.translation_id
	                   WHERE icl_translations_inner.trid = {$wpdb->prefix}icl_translations.trid
	                     AND icl_translations_status.status NOT IN ({$statuses})
	               ) < %d
	         ) as translations
			GROUP BY translations.post_type;
		";

		$untranslatedPosts = $wpdb->get_results(
			$wpdb->prepare( $query, Languages::getDefaultCode(), Lst::length( Languages::getSecondaries() ) ),
			ARRAY_N
		);

		// $setPluralPostName :: [ 'post' => '1' ] -> [ 'Posts' => 1 ]
		$setPluralPostName = function ( $postType ) {
			return [ PostType::getPluralName( $postType[0] )->getOrElse( $postType[0] ) => (int) $postType[1] ];
		};

		// $setCountToZero :: 'post' -> [ 'post' => 0 ]
		$setCountToZero = Lst::makePair( Fns::__, 0 );


		return wpml_collect( $postTypes )
			->map( $setCountToZero )
			->merge( $untranslatedPosts )
			->mapWithKeys( $setPluralPostName )
			->toArray();
	}
}
