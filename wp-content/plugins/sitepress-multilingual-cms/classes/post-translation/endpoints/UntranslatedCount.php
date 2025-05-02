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
		return wpml_collect( $this->runForPosts( $data, $wpdb ) )
			->merge( $this->runForPackages( $data, $wpdb ) )
			->toArray();
	}

	/**
	 * @param Collection $data
	 * @param \wpdb $wpdb
	 *
	 * @return array
	 */
	private function runForPosts( Collection $data, \wpdb $wpdb ) {
		$postTypes = $data->get( 'postTypes', PostTypes::getAutomaticTranslatable() );

		$postIn   = wpml_prepare_in( Fns::map( Str::concat( 'post_' ), $postTypes ) );
		$statuses = wpml_prepare_in( [ ICL_TM_NOT_TRANSLATED, ICL_TM_ATE_CANCELLED ] );

		$query = "
			SELECT translations.post_type, COUNT(translations.ID)
			FROM (
	            SELECT RIGHT(element_type, LENGTH(element_type) - 5) as post_type, posts.ID
	            FROM {$wpdb->prefix}icl_translations
	            INNER JOIN {$wpdb->prefix}posts posts ON element_id = ID
                
                LEFT JOIN {$wpdb->postmeta} postmeta ON postmeta.post_id = posts.ID AND postmeta.meta_key = %s
	                                        
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
	                     AND icl_translations_status.needs_update != 1
	               ) < %d
	               AND ( postmeta.meta_value IS NULL OR postmeta.meta_value = 'no' )
	         ) as translations
			GROUP BY translations.post_type;
		";

		$untranslatedPosts = Lst::length( $postTypes ) ? $wpdb->get_results(
			$wpdb->prepare( $query, \WPML_TM_Post_Edit_TM_Editor_Mode::POST_META_KEY_USE_NATIVE, Languages::getDefaultCode(), Lst::length( Languages::getSecondaries() ) ),
			ARRAY_N
		) : [];

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

	/**
	 * @param Collection $data
	 * @param \wpdb $wpdb
	 *
	 * @return array
	 */
	private function runForPackages( Collection $data, \wpdb $wpdb ) {
		if ( ! wpml_is_st_loaded() ) {
			return [];
		}

		$kinds = apply_filters( 'wpml_active_string_package_kinds', [] );
		if ( ! $kinds ) {
			return [];
		}

		$fullTypes = wpml_prepare_in( Fns::map( Str::concat( 'package_' ), array_keys( $kinds ) ) );
		$statuses  = wpml_prepare_in( [ ICL_TM_NOT_TRANSLATED, ICL_TM_ATE_CANCELLED ] );

		$untranslatedPackagesQuery = "
			SELECT translations.kind, COUNT(translations.ID)
			FROM (
	            SELECT RIGHT(element_type, LENGTH(element_type) - 8) as kind, packages.ID
	            FROM {$wpdb->prefix}icl_translations
	            INNER JOIN {$wpdb->prefix}icl_string_packages packages ON element_id = ID
                
                WHERE element_type IN ({$fullTypes})
		           AND source_language_code IS NULL
		           AND language_code = %s
		           AND (
	                   SELECT COUNT(trid)
	                   FROM {$wpdb->prefix}icl_translations icl_translations_inner
	                   INNER JOIN {$wpdb->prefix}icl_translation_status icl_translations_status
	                                       on icl_translations_inner.translation_id = icl_translations_status.translation_id
	                   WHERE icl_translations_inner.trid = {$wpdb->prefix}icl_translations.trid
	                     AND icl_translations_status.status NOT IN ({$statuses})
	                     AND icl_translations_status.needs_update != 1
	               ) < %d
	         ) as translations
			GROUP BY translations.kind;
		";
		$untranslatedPackages = $wpdb->get_results(
			$wpdb->prepare(
				$untranslatedPackagesQuery,
				Languages::getDefaultCode(),
				Lst::length( Languages::getSecondaries() )
			),
			ARRAY_N
		);

		return wpml_collect( $untranslatedPackages )
			->mapWithKeys( function( $packageType ) {
				return [ $packageType[0] => (int) $packageType[1] ];
			} )
			->toArray();
	}
}
