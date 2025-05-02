<?php

namespace WPML\StringTranslation\Infrastructure\Translation\Query;

class FindTranslationDataQueryBuilder {

	protected function getPrefix(): string {
		global $wpdb;
		return $wpdb->prefix;
	}

	/**
	 * @param int[]    $stringIds
	 * @param string[] $languageCodes
	 *
	 * @return string
	 */
	public function build( array $stringIds, array $languageCodes ) {
		$sql = "
            SELECT
                MAX(t.translation_id) as translation_id,
                string_batches.string_id as string_id,
                MAX(translation_status.rid) as rid,
                t.language_code as language_code
            FROM {$this->getPrefix()}icl_translations t
            INNER JOIN {$this->getPrefix()}icl_translations original_translation 
                ON original_translation.trid = t.trid
            INNER JOIN {$this->getPrefix()}icl_string_batches string_batches 
                ON string_batches.batch_id = original_translation.element_id
            LEFT JOIN {$this->getPrefix()}icl_translation_status translation_status
                ON translation_status.translation_id = t.translation_id
            WHERE t.language_code IN (" . wpml_prepare_in( $languageCodes, '%s' ) . ")
                AND original_translation.element_type = 'st-batch_strings' 
                AND string_batches.string_id IN (" . wpml_prepare_in( $stringIds, '%d' ) . ")
            GROUP BY t.language_code, string_batches.string_id
        ";

		return $sql;
	}
}
