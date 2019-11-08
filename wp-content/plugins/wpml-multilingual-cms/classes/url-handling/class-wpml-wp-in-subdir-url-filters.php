<?php

class WPML_WP_In_Subdir_URL_Filters implements IWPML_Action {

	/** @var WPML_Debug_BackTrace $backtrace */
	private $backtrace;

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_URL_Converter $url_converter */
	private $url_converter;

	/** @var string $uri_without_subdir */
	private $uri_without_subdir;

	/**
	 * @param WPML_Debug_BackTrace $backtrace
	 * @param SitePress            $sitepress
	 * @param WPML_URL_Converter   $url_converter
	 * @param string               $uri_without_subdir
	 */
	public function __construct(
		WPML_Debug_BackTrace $backtrace,
		SitePress $sitepress,
		WPML_URL_Converter $url_converter,
		$uri_without_subdir
	) {
		$this->url_converter      = $url_converter;
		$this->backtrace          = $backtrace;
		$this->sitepress          = $sitepress;
		$this->url_converter      = $url_converter;
		$this->uri_without_subdir = $uri_without_subdir;
	}

	public function add_hooks() {
		add_filter( 'home_url', array( $this, 'home_url_filter_on_parse_request' ), PHP_INT_MAX );
	}

	/**
	 * This filter is only applied in `WP::parse_request` in order to get
	 * the proper URI cleanup base in `$home_path_regex`
	 *
	 * @param string $home_url
	 *
	 * @return string
	 */
	public function home_url_filter_on_parse_request( $home_url ) {

		if ( $this->backtrace->is_class_function_in_call_stack( 'WP', 'parse_request' ) ) {

			if ( $this->request_uri_begins_with_lang() ) {
				/**
				 * The URL is already filtered with the lang directory.
				 * e.g. http://example.org/subdir/en/
				 */
				return $home_url;
			}

			/**
			 * We return the root URL of the WP install.
			 * e.g. http://example.org/subdir/
			 */
			return $this->url_converter->get_abs_home();
		}

		return $home_url;
	}

	/**
	 * @return bool
	 */
	private function request_uri_begins_with_lang() {
		$active_lang_codes = array_keys( $this->sitepress->get_active_languages() );
		$uri_parts         = explode( '/', trim( wpml_parse_url( $this->uri_without_subdir, PHP_URL_PATH ), '/' ) );

		return isset( $uri_parts[0] ) && in_array( $uri_parts[0], $active_lang_codes, true );
	}
}
