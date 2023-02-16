<?php

class WPML_ST_Translation_Memory_Records {

	/** @var wpdb $wpdb */
	private $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param array $strings
	 * @param string $source_lang
	 * @param string $target_lang
	 *
	 * @return array
	 */
	public function get( $strings, $source_lang, $target_lang ) {
		if ( ! $strings ) {
			return [];
		}

		$strings = $this->also_match_alternative_line_breaks( $strings );

		$prepared_strings = wpml_prepare_in( $strings );

		$sql = "
			SELECT s.value as original, coalesce(st.value, st.mo_string) as translation, st.language as language
			FROM {$this->wpdb->prefix}icl_strings as s
			JOIN {$this->wpdb->prefix}icl_string_translations as st
			ON s.id = st.string_id
			WHERE s.value IN ({$prepared_strings}) AND s.language = '%s'
				AND (
				(st.value IS NOT NULL AND st.status IN (" . ICL_STRING_TRANSLATION_COMPLETE . "," . ICL_STRING_TRANSLATION_NEEDS_UPDATE . "))
				OR (st.value IS NULL AND st.mo_string IS NOT NULL)
				)";

		$prepare_args = array( $source_lang );

		if ( $target_lang ) {
			$sql .= " AND st.language = '%s'";
			$prepare_args[] = $target_lang;
		} else {
			$sql .= " AND st.language <> '%s'";
			$prepare_args[] = $source_lang;
		}

		$records = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $prepare_args ) );

		$records = $this->also_include_matches_for_alternative_line_breaks( $records );

		return $records;
	}

	private function also_match_alternative_line_breaks( $strings ) {
		$new_strings = array();
		foreach ( $strings as $string ) {
			if ( mb_strpos( $string, "\r\n" ) !== false ) {
				$new_strings[] = str_replace( "\r\n", "\n", $string );
			}
			if ( mb_strpos( $string, "\n" ) !== false && mb_strpos( $string, "\r" ) === false ) {
				$new_strings[] = str_replace( "\n", "\r\n", $string );
			}
		}

		return array_merge( $strings, $new_strings );
	}

	private function also_include_matches_for_alternative_line_breaks( $records ) {
		$new_records = array();
		foreach ( $records as $record ) {
			if ( mb_strpos( $record->original, "\r\n" ) !== false ) {
				$new_record = clone $record;
				$new_record->original = str_replace( "\r\n", "\n", $record->original );
				$new_records[] = $new_record;
			}
			if ( mb_strpos( $record->original, "\n" ) !== false && mb_strpos( $record->original, "\r" ) === false ) {
				$new_record = clone $record;
				$new_record->original = str_replace( "\n", "\r\n", $record->original );
				$new_records[] = $new_record;
			}
		}

		return array_merge( $records, $new_records );
	}
}
