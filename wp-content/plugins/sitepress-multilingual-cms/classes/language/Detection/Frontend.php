<?php

namespace WPML\Language\Detection;

use WPML\FP\Maybe;
use WPML\FP\Obj;
use \WPML_Request;
use \WPML_WP_Comments;
use function WPML\FP\System\filterVar;

/**
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class Frontend extends WPML_Request {
	/** @var \WPML_WP_API */
	private $wp_api;

	public function __construct(
		\WPML_URL_Converter $url_converter,
		$active_languages,
		$default_language,
		CookieLanguage $cookieLanguage,
		\WPML_WP_API $wp_api
	) {
		parent::__construct( $url_converter, $active_languages, $default_language, $cookieLanguage );
		$this->wp_api = $wp_api;
	}


	public function get_requested_lang() {
		return $this->wp_api->is_comments_post_page() ? $this->get_comment_language() : $this->get_request_uri_lang();
	}

	/**
	 * @return string
	 */
	private function get_comment_language() {
		return Maybe::of( $_POST )
					->map( Obj::prop( WPML_WP_Comments::LANG_CODE_FIELD ) )
					->map( filterVar( FILTER_SANITIZE_SPECIAL_CHARS ) )
					->getOrElse( $this->default_language );

	}

	protected function get_cookie_name() {
		return $this->cookieLanguage->getFrontendCookieName();
	}
}
