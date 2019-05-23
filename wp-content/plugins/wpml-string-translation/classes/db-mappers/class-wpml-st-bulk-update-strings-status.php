<?php

class WPML_ST_Bulk_Update_Strings_Status {

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var array $active_lang_codes */
	private $active_lang_codes;

	public function __construct( wpdb $wpdb, array $active_lang_codes ) {
		$this->wpdb              = $wpdb;
		$this->active_lang_codes = $active_lang_codes;
	}

	/**
	 * This bulk process was transposed from PHP code
	 *
	 * @see WPML_ST_String::update_status
	 *
	 * Important: The order we call each method is important because it reflects
	 * the order of the conditions in WPML_ST_String::update_status. The updated IDs
	 * will not be updated anymore in the subsequent calls.
	 *
	 * @return array updated IDs
	 */
	public function run() {
		$updated_ids = $this->update_strings_with_no_translation();
		$updated_ids = $this->update_strings_with_all_translations_not_translated( $updated_ids );
		$updated_ids = $this->update_strings_with_one_translation_waiting_for_translator( $updated_ids );
		$updated_ids = $this->update_strings_with_one_translation_needs_update( $updated_ids );
		$updated_ids = $this->update_strings_with_less_translations_than_langs_and_one_translation_completed( $updated_ids );
		$updated_ids = $this->update_strings_with_less_translations_than_langs_and_no_translation_completed( $updated_ids );
		$updated_ids = $this->update_remaining_strings_with_one_not_translated( $updated_ids );
		$updated_ids = $this->update_remaining_strings( $updated_ids );

		return $updated_ids;
	}

	/**
	 * @return array
	 */
	private function update_strings_with_no_translation() {
		$ids = $this->wpdb->get_col(
			"SELECT DISTINCT s.id FROM {$this->wpdb->prefix}icl_strings AS s
			 LEFT JOIN {$this->wpdb->prefix}icl_string_translations AS st ON st.string_id = s.id
			 WHERE st.string_id IS NULL"
		);

		$this->update_strings_status( $ids, ICL_TM_NOT_TRANSLATED );

		return $ids;
	}

	/**
	 * @param array $updated_ids
	 *
	 * @return array
	 */
	private function update_strings_with_all_translations_not_translated( array $updated_ids ) {
		$subquery_not_exists = $this->get_translations_snippet()
			. $this->wpdb->prepare( " AND (st.status != %d OR (st.mo_string != '' AND st.mo_string IS NOT NULL))", ICL_TM_NOT_TRANSLATED );

		$ids = $this->wpdb->get_col(
			"SELECT DISTINCT s.id FROM {$this->wpdb->prefix}icl_strings AS s
			 WHERE NOT EXISTS(" . $subquery_not_exists . ")"
				. $this->get_and_not_in_updated_snippet( $updated_ids )
		);

		$this->update_strings_status( $ids, ICL_TM_NOT_TRANSLATED );

		return array_merge( $updated_ids, $ids );
	}

	/**
	 * @param array $updated_ids
	 *
	 * @return array
	 */
	private function update_strings_with_one_translation_waiting_for_translator( array $updated_ids ) {
		$subquery = $this->get_translations_snippet()
			. $this->wpdb->prepare( " AND st.status = %d", ICL_TM_WAITING_FOR_TRANSLATOR );

		return $this->update_string_ids_if_subquery_exists( $subquery, $updated_ids, ICL_TM_WAITING_FOR_TRANSLATOR );
	}

	/**
	 * @param array $updated_ids
	 *
	 * @return array
	 */
	private function update_strings_with_one_translation_needs_update( array $updated_ids ) {
		$subquery = $this->get_translations_snippet()
			. $this->wpdb->prepare( " AND st.status = %d", ICL_TM_NEEDS_UPDATE );

		return $this->update_string_ids_if_subquery_exists( $subquery, $updated_ids, ICL_TM_NEEDS_UPDATE );
	}

	/**
	 * @param array $updated_ids
	 *
	 * @return array
	 */
	private function update_strings_with_less_translations_than_langs_and_one_translation_completed( array $updated_ids ) {
		$subquery = $this->get_translations_snippet()
			. $this->wpdb->prepare( " AND (st.status = %d OR (st.mo_string != '' AND st.mo_string IS NOT NULL))", ICL_TM_COMPLETE )
			. $this->get_and_translations_less_than_secondary_languages_snippet();

		return $this->update_string_ids_if_subquery_exists( $subquery, $updated_ids, ICL_STRING_TRANSLATION_PARTIAL );
	}

	/**
	 * @param array $updated_ids
	 *
	 * @return array
	 */
	private function update_strings_with_less_translations_than_langs_and_no_translation_completed( array $updated_ids ) {
		$subquery = $this->get_translations_snippet()
			. $this->wpdb->prepare( " AND st.status != %d", ICL_TM_COMPLETE )
			. $this->get_and_translations_less_than_secondary_languages_snippet();

		return $this->update_string_ids_if_subquery_exists( $subquery, $updated_ids, ICL_TM_NOT_TRANSLATED );
	}

	/**
	 * Defaults to ICL_STRING_TRANSLATION_PARTIAL if not caught before
	 *
	 * @param array $updated_ids
	 *
	 * @return array
	 */
	private function update_remaining_strings_with_one_not_translated( array $updated_ids ) {
		$subquery = $this->get_translations_snippet()
			. $this->wpdb->prepare( " AND st.status = %d AND (st.mo_string = '' OR st.mo_string IS NULL)", ICL_TM_NOT_TRANSLATED );

		return $this->update_string_ids_if_subquery_exists( $subquery, $updated_ids, ICL_STRING_TRANSLATION_PARTIAL );
	}

	/**
	 * Defaults to ICL_TM_COMPLETE if not caught before
	 *
	 * @param array $updated_ids
	 *
	 * @return array
	 */
	private function update_remaining_strings( array $updated_ids ) {
		$subquery = $this->get_translations_snippet();

		return $this->update_string_ids_if_subquery_exists( $subquery, $updated_ids, ICL_TM_COMPLETE );
	}

	/**
	 * @param string $subquery
	 * @param array  $updated_ids
	 * @param int    $new_status
	 *
	 * @return array
	 */
	private function update_string_ids_if_subquery_exists( $subquery, array $updated_ids, $new_status ) {
		$ids = $this->wpdb->get_col(
			"SELECT DISTINCT s.id FROM {$this->wpdb->prefix}icl_strings AS s
			 WHERE EXISTS(" . $subquery . ")"
			. $this->get_and_not_in_updated_snippet( $updated_ids )
		);

		$this->update_strings_status( $ids, $new_status );

		return array_merge( $updated_ids, $ids );
	}

	/**
	 * Subquery for the string translations
	 *
	 * @return string
	 */
	private function get_translations_snippet() {
		return "SELECT DISTINCT st.string_id
				FROM {$this->wpdb->prefix}icl_string_translations AS st
				WHERE st.string_id = s.id
					AND st.language != s.language";
	}

	/**
	 * Subquery where translations are less than the number of secondary languages:
	 * - the string translation language must be different than the string language
	 * - the string translation language must be part of the active languages
	 *
	 * @return string
	 */
	private function get_and_translations_less_than_secondary_languages_snippet() {
		$secondary_languages_count = count( $this->active_lang_codes ) - 1;

		return $this->wpdb->prepare( " AND (
					SELECT COUNT( st2.id )
					FROM {$this->wpdb->prefix}icl_string_translations AS st2
					WHERE st2.string_id = s.id
						AND st2.language != s.language
						AND st2.language IN(" . wpml_prepare_in( $this->active_lang_codes ) . ")
			) < %d",
			$secondary_languages_count
		);
	}

	/**
	 * @param array $updated_ids
	 *
	 * @return string
	 */
	private function get_and_not_in_updated_snippet( array $updated_ids ) {
		return " AND s.id NOT IN(" . wpml_prepare_in( $updated_ids ) . ")";
	}

	/**
	 * @param array $ids
	 * @param int   $status
	 */
	private function update_strings_status( array $ids, $status ) {
		if ( ! $ids ) {
			return;
		}

		$this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$this->wpdb->prefix}icl_strings SET status = %d WHERE id IN(" . wpml_prepare_in( $ids ) . ")",
				$status
			)
		);
	}
}
