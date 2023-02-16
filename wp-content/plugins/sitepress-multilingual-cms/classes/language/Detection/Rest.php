<?php

namespace WPML\Language\Detection;

use \WPML_Request;

class Rest extends WPML_Request {
	/** @var Backend */
	private $backend;

	public function __construct(
		$url_converter,
		$active_languages,
		$default_language,
		$cookieLanguage,
		$backend
	) {
		parent::__construct( $url_converter, $active_languages, $default_language, $cookieLanguage );
		$this->backend = $backend;
	}


	protected function get_cookie_name() {
		return $this->cookieLanguage->getAjaxCookieName( ! $this->getFrontendLanguage() );
	}

	public function get_requested_lang() {
		return $this->getFrontendLanguage() ?: $this->backend->get_requested_lang();
	}

	/**
	 * It tries to detect language in FRONTEND manner.
	 *
	 * We ignore a default language due to fallback mechanism in WPML_URL_Converter_Subdir_Strategy which never returns
	 * NULL when `use_directory_for_default_lang` option is enabled.
	 *
	 * @return string|null
	 */
	private function getFrontendLanguage() {
		$language = $this->get_request_uri_lang();

		return $language && $language !== $this->default_language ? $language : null;
	}
}
