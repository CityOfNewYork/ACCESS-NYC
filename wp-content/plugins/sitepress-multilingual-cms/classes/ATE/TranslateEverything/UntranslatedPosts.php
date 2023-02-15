<?php

namespace WPML\TM\ATE\TranslateEverything;

use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;

class UntranslatedPosts {

	public static function get( array $secondaryLanguages, $postType, $queueSize ) {
		global $wpdb;

		$languagesPart      = Lst::join( ' UNION ALL ', Fns::map( Str::replace( '__', Fns::__, "SELECT '__' AS code" ), $secondaryLanguages ) );
		$acceptableStatuses = ICL_TM_NOT_TRANSLATED . ', ' . ICL_TM_ATE_CANCELLED;

		$sql = "
			SELECT original_element.element_id, languages.code
			FROM {$wpdb->prefix}icl_translations original_element
			INNER JOIN ( $languagesPart ) as languages
			LEFT JOIN {$wpdb->prefix}icl_translations translations ON translations.trid = original_element.trid AND translations.language_code = languages.code
			LEFT JOIN {$wpdb->prefix}icl_translation_status translation_status ON translation_status.translation_id = translations.translation_id
			
			INNER JOIN {$wpdb->posts} posts ON posts.ID = original_element.element_id
			
			WHERE original_element.element_type = %s and original_element.source_language_code IS NULL AND (
			        translation_status.status IS NULL OR translation_status.status IN ({$acceptableStatuses}) OR translation_status.needs_update = 1
			) AND posts.post_status IN ( 'publish', 'inherit' )
			ORDER BY original_element.element_id, languages.code
			LIMIT %d
		";

		$result = $wpdb->get_results( $wpdb->prepare( $sql, 'post_' . $postType, $queueSize ), ARRAY_N );

		return Fns::map( Obj::evolve( [ 0 => Cast::toInt() ] ), $result );
	}

}