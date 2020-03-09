<?php

class WPML_REST_Request_Analyze_Factory {

	/**
	 * @return WPML_REST_Request_Analyze
	 */
	public static function create() {
		/**
		 * @var \WPML_URL_Converter       $wpml_url_converter
		 * @var \WPML_Language_Resolution $wpml_language_resolution
		 */
		global $wpml_url_converter, $wpml_language_resolution;

		return new WPML_REST_Request_Analyze(
			$wpml_url_converter,
			$wpml_language_resolution->get_active_language_codes(),
			new WP_Rewrite()
		);
	}
}
