<?php

/**
 * Class WPML_Request
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 *
 * @abstract
 */

use WPML\Language\Detection\CookieLanguage;

abstract class WPML_Request {

	/** @var  WPML_URL_Converter */
	protected $url_converter;
	protected $active_languages;
	protected $default_language;

	/** @var CookieLanguage  */
	protected $cookieLanguage;

	/**
	 * @param  WPML_URL_Converter $url_converter
	 * @param  array              $active_languages
	 * @param  string             $default_language
	 * @param  CookieLanguage     $cookieLanguage
	 */
	public function __construct(
		WPML_URL_Converter $url_converter,
		$active_languages,
		$default_language,
		CookieLanguage $cookieLanguage
	) {
		$this->url_converter    = $url_converter;
		$this->active_languages = $active_languages;
		$this->default_language = $default_language;
		$this->cookieLanguage   = $cookieLanguage;
	}

	abstract protected function get_cookie_name();

	/**
	 * Determines the language of the current request.
	 *
	 * @return string|false language code of the current request, determined from the requested url and the user's
	 *                      cookie.
	 */
	abstract public function get_requested_lang();

	/**
	 * Returns the current REQUEST_URI optionally filtered
	 *
	 * @param null|int $filter filter to apply to the REQUEST_URI, takes the same arguments
	 *                         as filter_var for the filter type.
	 *
	 * @return string
	 */
	public function get_request_uri( $filter = null ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/';
		if ( $filter !== null ) {
			$request_uri = filter_var( $request_uri, $filter );
		}

		return $request_uri;
	}

	/**
	 * @global $wpml_url_converter
	 *
	 * @return string|false language code that can be determined from the currently requested URI.
	 */
	public function get_request_uri_lang() {
		$req_url = isset( $_SERVER['HTTP_HOST'] )
			? untrailingslashit( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) : '';

		return $this->url_converter->get_language_from_url( $req_url );
	}

	/**
	 * @return string language code stored in the user's wp-wpml_current_language cookie
	 */
	public function get_cookie_lang() {
		return $this->cookieLanguage->get( $this->get_cookie_name() );
	}

	/**
	 * Checks whether hidden languages are to be displayed at the moment.
	 * They are displayed in the frontend if the users has the respective option icl_show_hidden_languages set in his
	 * user_meta. The are displayed in the backend for all admins with manage_option capabilities.
	 *
	 * @return bool true if hidden languages are to be shown
	 */
	public function show_hidden() {

		return ! did_action( 'init' )
			   || ( get_user_meta( get_current_user_id(), 'icl_show_hidden_languages', true )
					|| ( ( is_admin() || wpml_is_rest_request() ) && current_user_can( 'manage_options' ) ) );
	}

	/**
	 * Sets the language code of the current screen in the User's wp-wpml_current_language cookie
	 *
	 * When user is not logged we must set cookie with JS to avoid issues with cached pages
	 *
	 * @param string $lang_code
	 */
	public function set_language_cookie( $lang_code ) {
		$this->cookieLanguage->set( $this->get_cookie_name(), $lang_code );
	}
}
