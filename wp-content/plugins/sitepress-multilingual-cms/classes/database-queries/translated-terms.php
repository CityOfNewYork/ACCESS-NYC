<?php

namespace WPML\DatabaseQueries;

class TranslatedTerms {

	/**
	 * Returns array of translated term IDs that are related to defined secondary languages.
	 *
	 * @param array $langs Array of secondary languages codes to get translated terms IDs.
	 *
	 * @return array
	 */
	public static function getIdsForLangs( $langs ) {
		global $wpdb;
		$languagesIn = wpml_prepare_in( $langs );

		$contentIdsQuery = "
		SELECT
			wpt.term_id,
			wptt.taxonomy
		FROM
			{$wpdb->terms} wpt
		INNER JOIN {$wpdb->term_taxonomy} wptt ON
			wptt.term_id = wpt.term_id
		INNER JOIN {$wpdb->prefix}icl_translations iclt ON
			iclt.element_id = wptt.term_taxonomy_id AND iclt.element_type = CONCAT('tax_', wptt.taxonomy)
		WHERE
			iclt.language_code IN({$languagesIn})
		";

		return $wpdb->get_results( $contentIdsQuery );
	}
}
