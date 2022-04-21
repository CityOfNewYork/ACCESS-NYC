<?php

use WPML\Language\Detection\CookieLanguage;
use WPML\Language\Detection\Frontend;
use WPML\Language\Detection\Backend;
use WPML\Language\Detection\Rest;
use WPML\Language\Detection\Ajax;
use function WPML\Container\make;


function wpml_load_request_handler( $is_admin, $active_language_codes, $default_language ) {
	/**
	 * @var WPML_Request $wpml_request_handler
	 * @var WPML_URL_Converter $wpml_url_converter
	 */
	global $wpml_request_handler, $wpml_url_converter;

	$cookieLanguage       = new CookieLanguage( new WPML_Cookie(), $default_language );
	$rest_request_analyze = make( \WPML_REST_Request_Analyze::class );

	$createBackend = function () use (
		$wpml_url_converter,
		$active_language_codes,
		$default_language,
		$cookieLanguage
	) {
		return new Backend(
			$wpml_url_converter,
			$active_language_codes,
			$default_language,
			$cookieLanguage
		);
	};

	if ( $rest_request_analyze->is_rest_request() ) {
		$wpml_request_handler = new Rest(
			$wpml_url_converter,
			$active_language_codes,
			$default_language,
			$cookieLanguage,
			$createBackend()
		);
	} elseif ( $is_admin ) {
		if ( wpml_is_ajax() ) {
			$wpml_request_handler = new Ajax(
				$wpml_url_converter,
				$active_language_codes,
				$default_language,
				$cookieLanguage
			);
		} else {
			$wpml_request_handler = $createBackend();
		}
	} else {
		$wpml_request_handler = new Frontend(
			$wpml_url_converter,
			$active_language_codes,
			$default_language,
			$cookieLanguage,
			new WPML_WP_API()
		);
	}

	return $wpml_request_handler;
}
