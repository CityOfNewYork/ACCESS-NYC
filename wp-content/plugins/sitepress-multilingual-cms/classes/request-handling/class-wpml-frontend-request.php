<?php

use WPML\Language\Detection\Frontend;

/*
 * @deprecated deprecated since version 4.4.0
 * This class has been replaced by WPML\Language\Detection\Frontend and is going to be removed in the next major release.
 *
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 *
 */
class WPML_Frontend_Request extends WPML_Request {
	/** @var \WPML\Language\Detection\Frontend */
	private $frontend;

	public function __construct( $url_converter, $active_languages, $default_language, $cookieLanguage, $wp_api ) {
		parent::__construct( $url_converter, $active_languages, $default_language, $cookieLanguage );
		$this->frontend = new Frontend(
			$url_converter,
			$active_languages,
			$default_language,
			$cookieLanguage,
			$wp_api
		);
	}

	/**
	 * @deprecated deprecated since version 4.4.0
	 * @return false|string
	 */
	public function get_requested_lang() {
		return $this->frontend->get_requested_lang();
	}

	protected function get_cookie_name() {
		return $this->cookieLanguage->getFrontendCookieName();
	}
}
