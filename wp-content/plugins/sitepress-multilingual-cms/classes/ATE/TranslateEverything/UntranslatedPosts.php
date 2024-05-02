<?php

namespace WPML\TM\ATE\TranslateEverything;

use WPML\Element\API\Languages;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;

class UntranslatedPosts {

	/**
	 * @param string[] $secondaryLanguages
	 * @param string $postType e.g. 'post', 'page'
	 * @param $queueSize
	 *
	 * @return array{0: int, 1: int} [ [element_id1, language_code1], [element_id1, language_code2], [element_id2, language_code3], ... ]
	 */
	public function get( array $secondaryLanguages, $postType, $queueSize ) {
		global $wpdb;

		if ( empty( $secondaryLanguages ) ) {
			// Without secondaryLanguages there won't be any posts, and
			// the following query will throw an error.
			return [];
		}

		$languagesPart      = Lst::join( ' UNION ALL ', Fns::map( Str::replace( '__', Fns::__, "SELECT '__' AS code" ), $secondaryLanguages ) );
		$acceptableStatuses = ICL_TM_NOT_TRANSLATED . ', ' . ICL_TM_ATE_CANCELLED;

		$sql = "
			SELECT original_element.element_id, languages.code
			FROM {$wpdb->prefix}icl_translations original_element
			INNER JOIN ( $languagesPart ) as languages
			LEFT JOIN {$wpdb->prefix}icl_translations translations ON translations.trid = original_element.trid AND translations.language_code = languages.code
			LEFT JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
			
			INNER JOIN {$wpdb->posts} posts ON posts.ID = original_element.element_id
			
			LEFT JOIN {$wpdb->postmeta} postmeta ON postmeta.post_id = posts.ID AND postmeta.meta_key = %s
			
			WHERE original_element.element_type = %s 
			  	AND original_element.source_language_code IS NULL
			    AND original_element.language_code = %s
			    AND ( translation_status.status IS NULL OR translation_status.status IN ({$acceptableStatuses}) OR translation_status.needs_update = 1) 
			    AND posts.post_status IN ( 'publish', 'inherit' )
			AND ( postmeta.meta_value IS NULL OR postmeta.meta_value = 'no' )
			ORDER BY original_element.element_id, languages.code
			LIMIT %d
		";

		$result = $wpdb->get_results( $wpdb->prepare(
			$sql,
			\WPML_TM_Post_Edit_TM_Editor_Mode::POST_META_KEY_USE_NATIVE,
			'post_' . $postType,
			Languages::getDefaultCode(),
			$queueSize
		), ARRAY_N );

		return Fns::map( Obj::evolve( [ 0 => Cast::toInt() ] ), $result );
	}

}