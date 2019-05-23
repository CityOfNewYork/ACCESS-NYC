<?php

class WPML_ST_JED_Locales_Domains_Mapper {

	/** @var wpdb $wpdb */
	private $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/** @param array $string_translation_ids */
	public function get_from_translation_ids( array $string_translation_ids ) {
		return $this->wpdb->get_results("
			SELECT DISTINCT s.context AS domain, l.default_locale AS locale
			FROM {$this->wpdb->prefix}icl_string_translations AS st
			JOIN {$this->wpdb->prefix}icl_strings AS s ON s.id = st.string_id
			JOIN {$this->wpdb->prefix}icl_languages AS l ON l.code = st.language
			WHERE st.id IN(" . wpml_prepare_in( $string_translation_ids ) . ")
		");
	}
}
