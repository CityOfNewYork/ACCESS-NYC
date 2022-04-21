<?php

namespace WPML\Posts;

use WPML\API\PostTypes;
use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\LIB\WP\PostType;

class CountPerPostType {
	public function run( Collection $data, \wpdb $wpdb ) {
		$postTypes = $data->get( 'postTypes', PostTypes::getAutomaticTranslatable() );
		$postIn    = wpml_prepare_in( $postTypes );

		$query = "
			SELECT posts.post_type, COUNT(posts.ID)
			FROM {$wpdb->posts} posts
			INNER JOIN {$wpdb->prefix}icl_translations translations ON translations.element_id = posts.ID AND translations.element_type = CONCAT('post_', posts.post_type)
			WHERE posts.post_type IN ({$postIn}) AND posts.post_status = %s	AND translations.source_language_code IS NULL		
			GROUP BY posts.post_type
		";

		$postCountPerType = $wpdb->get_results( $wpdb->prepare( $query, 'publish' ), ARRAY_N );

		// $setPluralPostName :: [ 'post' => '1' ] -> [ 'Posts' => 1 ]
		$setPluralPostName = function ( $postType ) {
			return [ PostType::getPluralName( $postType[0] )->getOrElse( $postType[0] ) => (int) $postType[1] ];
		};

		// $setCountToZero :: 'post' -> [ 'post' => 0 ]
		$setCountToZero = Lst::makePair( Fns::__, 0 );

		return wpml_collect( $postTypes )
			->map( $setCountToZero )
			->merge( $postCountPerType )
			->mapWithKeys( $setPluralPostName )
			->toArray();
	}
}