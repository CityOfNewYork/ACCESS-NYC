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
use WPML\UrlHandling\WPLoginUrlConverterRules;
use WPML\FP\Obj;

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
		/**
		 * Avoid returning language from URL when wpml_should_skip_saving_language_in_cookies filter hook returns TRUE
		 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-1544
		 */
		if ( apply_filters( 'wpml_should_skip_saving_language_in_cookies', false ) ) {
			return false;
		}

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
		$queryVars = [];
		if ( isset( $_SERVER['QUERY_STRING'] ) ) {
			parse_str( $_SERVER['QUERY_STRING'], $queryVars );
		}

		$isReviewPostPage = (
			Obj::has( 'wpmlReviewPostType', $queryVars ) &&
			Obj::has( 'preview_id', $queryVars ) &&
			Obj::has( 'preview_nonce', $queryVars ) &&
			Obj::has( 'preview', $queryVars ) &&
			Obj::has( 'jobId', $queryVars ) &&
			Obj::has( 'returnUrl', $queryVars )
		);

		$isPostsListPage = false;
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$uri = urldecode( $_SERVER['REQUEST_URI'] );
			if ( is_admin() && $uri && strpos( $uri, 'edit.php' ) !== false ) {
				$isPostsListPage = true;
			}
		}

		return ! did_action( 'init' )
			   || ( get_user_meta( get_current_user_id(), 'icl_show_hidden_languages', true )
					|| ( ( is_admin() || wpml_is_rest_request() ) && $this->isAdmin() ) )
			  || ( ( $isPostsListPage || $isReviewPostPage ) && ( $this->isAdmin() || $this->isEditor() ) )
			  || ( $isReviewPostPage && is_user_logged_in() && $this->isSubscriber() );
	}

	/**
	 * @return boolean
	 */
	private function isAdmin() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @return boolean
	 */
	private function isEditor() {
		$can = $this->getCaps();

		return $can['read'] && $can['publish'] && $can['edit'] && \WPML\LIB\WP\User::isTranslator();
	}

	/**
	 * @return boolean
	 */
	private function isSubscriber() {
		return current_user_can( 'read' ) && \WPML\LIB\WP\User::isTranslator();
	}

	/**
	 * @return array
	 */
	private function getCaps() {
		$canPublish   = current_user_can( 'publish_pages' ) || current_user_can( 'publish_posts' );
		$canRead      = current_user_can( 'read_private_pages' ) || current_user_can( 'read_private_posts' );
		$canEdit      = current_user_can( 'edit_posts' );

		return [
			'publish' => $canPublish,
			'read'    => $canRead,
			'edit'    => $canEdit,
		];
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
