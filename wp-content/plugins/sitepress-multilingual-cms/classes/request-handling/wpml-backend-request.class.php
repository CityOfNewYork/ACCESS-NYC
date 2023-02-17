<?php

use WPML\Language\Detection\Backend;

/**
 * @deprecated This class has been replaced by WPML\Language\Detection\Backend and is going to be removed in the next major release.
 * @since 4.4.0
 * @see WPML\Language\Detection\Backend
 *
 * @package    wpml-core
 * @subpackage wpml-requests
 */
class WPML_Backend_Request extends WPML_Request {
	/** @var Backend */
	private $backend;

	public function __construct( $url_converter, $active_languages, $default_language, $cookieLanguage ) {
		parent::__construct( $url_converter, $active_languages, $default_language, $cookieLanguage );
		$this->backend = new Backend(
			$url_converter,
			$active_languages,
			$default_language,
			$cookieLanguage
		);
	}

	/**
	 * @return false|string
	 */
	public function get_requested_lang() {
		return $this->backend->get_requested_lang();
	}

	protected function get_cookie_name() {
		return $this->cookieLanguage->getBackendCookieName();
	}
}
