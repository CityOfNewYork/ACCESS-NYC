<?php

class WPML_REST_Request_Analyze {

	/** @var WPML_URL_Converter $url_converter */
	private $url_converter;

	/** @var array $active_language_codes */
	private $active_language_codes;

	/** @var WP_Rewrite $wp_rewrite */
	private $wp_rewrite;

	/** @var array $uri_parts */
	private $uri_parts;

	public function __construct(
		WPML_URL_Converter $url_converter,
		array $active_language_codes,
		WP_Rewrite $wp_rewrite
	) {
		$this->url_converter         = $url_converter;
		$this->active_language_codes = $active_language_codes;
		$this->wp_rewrite            = $wp_rewrite;
	}

	/** @return bool */
	public function is_rest_request() {
		if ( array_key_exists( 'rest_route', $_REQUEST ) ) {
			return true;
		}

		$rest_url_prefix = 'wp-json';

		if ( function_exists( 'rest_get_url_prefix' ) ) {
			$rest_url_prefix = rest_get_url_prefix();
		}

		$uri_part = $this->get_uri_part( $this->has_valid_language_prefix() ? 1 : 0 );

		return $uri_part === $rest_url_prefix;
	}

	/** @return bool */
	private function has_valid_language_prefix() {
		if ( $this->url_converter->get_strategy() instanceof WPML_URL_Converter_Subdir_Strategy ) {
			$maybe_lang = $this->get_uri_part();
			return in_array( $maybe_lang, $this->active_language_codes, true );
		}

		return false;
	}

	/**
	 * @param int $index
	 *
	 * @return string
	 */
	private function get_uri_part( $index = 0 ) {
		if ( null === $this->uri_parts ) {
			$request_uri = (string) filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );
			$cleaned_uri = ltrim( wpml_strip_subdir_from_url( $request_uri ), '/' );

			if ( $this->wp_rewrite->using_index_permalinks() ) {
				$cleaned_uri = preg_replace( '/^' . $this->wp_rewrite->index . '\//', '', $cleaned_uri, 1 );
			}

			$this->uri_parts = explode( '/', $cleaned_uri );
		}

		return isset( $this->uri_parts[ $index ] ) ? $this->uri_parts[ $index ] : '';
	}
}
