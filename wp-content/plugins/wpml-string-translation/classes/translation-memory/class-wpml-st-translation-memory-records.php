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
	public function get( $strings, $source_lang, $target_lang, $context = null, $gettext_context = null ) {
		if ( ! $strings ) {
			return [];
		}

		$strings          = $this->also_match_alternative_line_breaks( $strings );
		$prepared_strings = wpml_prepare_in( $strings );

		$base_sql = "
        SELECT s.value as original, coalesce(st.value, st.mo_string) as translation, st.language as language
        FROM {$this->wpdb->prefix}icl_strings as s
        JOIN {$this->wpdb->prefix}icl_string_translations as st
        ON s.id = st.string_id
        WHERE s.value IN ({$prepared_strings}) AND s.language = '%s'
            AND (
             (st.value IS NOT NULL AND st.status IN (" . ICL_STRING_TRANSLATION_COMPLETE . "," . ICL_STRING_TRANSLATION_NEEDS_UPDATE . "))
             OR (st.value IS NULL AND st.mo_string IS NOT NULL)
            )";

		$prepare_args  = [ $source_lang ];
		$context_where = '';

		if ( $context ) {
			$context_where  .= " AND s.context = '%s'";
			$prepare_args[ ] = $context;
		}

		if ( $gettext_context ) {
			$context_where  .= " AND s.gettext_context = '%s'";
			$prepare_args[ ] = $gettext_context;
		}

		list( $language_where, $prepare_args ) = $this->add_language_where( $prepare_args, $target_lang, $source_lang );

		$records = $this->wpdb->get_results( $this->wpdb->prepare( $base_sql . $context_where . $language_where, $prepare_args ) );

		/**
		 * Fallback request to fetch any available translation for the string regardless of context and gettext_context.
		 *
		 * This functionality is needed if a particular string doesn't have a translation, but the same string in a different
		 * domain does have.
		 */
		if ( empty( $records ) && ( $context || $gettext_context ) ) {
			$prepare_args  = [ $source_lang ];

			list( $language_where, $prepare_args ) = $this->add_language_where( $prepare_args, $target_lang, $source_lang );

			$records = $this->wpdb->get_results( $this->wpdb->prepare( $base_sql . $language_where, $prepare_args ) );
		}

		return $this->also_include_matches_for_alternative_line_breaks( $records );
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

	private function add_language_where( array $prepare_args, string $target_lang, string $source_lang ) {
		$language_where = '';

		if ( $target_lang ) {
			$language_where .= " AND st.language = '%s'";
			$prepare_args[]  = $target_lang;
		} else {
			$language_where .= " AND st.language <> '%s'";
			$prepare_args[]  = $source_lang;
		}
		return array( $language_where, $prepare_args );
	}
}
