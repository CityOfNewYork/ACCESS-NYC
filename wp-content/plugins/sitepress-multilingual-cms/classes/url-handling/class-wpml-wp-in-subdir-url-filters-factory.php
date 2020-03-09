<?php

class WPML_WP_In_Subdir_URL_Filters_Factory implements IWPML_Frontend_Action_Loader, IWPML_Backend_Action_Loader {

	public function create() {
		/**
		 * @var WPML_URL_Converter $wpml_url_converter
		 * @var SitePress          $sitepress
		 */
		global $wpml_url_converter, $sitepress;

		$lang_negotiation_type = $sitepress->get_setting( 'language_negotiation_type', false );

		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === (int) $lang_negotiation_type ) {
			$request_uri        = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_STRING );
			$uri_without_subdir = wpml_strip_subdir_from_url( $request_uri );

			if ( trim( $request_uri, '/' ) !== trim( $uri_without_subdir, '/' ) ) {
				$backtrace = new WPML_Debug_BackTrace( null, 5 );
				return new WPML_WP_In_Subdir_URL_Filters( $backtrace, $sitepress, $wpml_url_converter, $uri_without_subdir );
			}
		}

		return null;
	}
}
