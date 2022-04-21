<?php

namespace WPML\Core\REST;

class RewriteRules implements \IWPML_REST_Action, \IWPML_DIC_Action {
	/** @var \SitePress */
	private $sitepress;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_action( 'init', [ $this, 'addOptionRewriteRulesHook' ] );
	}

	public function addOptionRewriteRulesHook() {
		if ( $this->isLangInDirectory() && $this->isUseDirectoryForDefaultLanguage() && $this->isInstalledInSubdirectory() ) {
			add_filter( 'option_rewrite_rules', [ $this, 'updateRules' ] );
		}
	}

	public function updateRules( $rewriteRules ) {
		if ( ! is_array( $rewriteRules ) ) {
			return $rewriteRules;
		}

		$subdirectory = $this->getSubdirectory();

		$mapKeys = function ( $value, $key ) use ( $subdirectory ) {
			if ( $key === '^wp-json/?$' ) {
				$key = "^($subdirectory/)?wp-json/?$";
			} elseif ( $key === '^wp-json/(.*)?' ) {
				$key   = "^($subdirectory/)?wp-json/(.*)?";
				$value = str_replace( 'matches[1]', 'matches[2]', $value );
			}

			return [ $key => $value ];
		};

		return \wpml_collect( $rewriteRules )->mapWithKeys( $mapKeys )->toArray();
	}

	/**
	 * @return bool
	 */
	private function isLangInDirectory() {
		return (int) $this->sitepress->get_setting( 'language_negotiation_type' ) === WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY;
	}

	/**
	 * @return bool
	 */
	private function isUseDirectoryForDefaultLanguage() {
		$urlSettings = $this->sitepress->get_setting( 'urls' );

		return isset( $urlSettings['directory_for_default_language'] ) && $urlSettings['directory_for_default_language'];
	}

	/**
	 * @return bool
	 */
	private function isInstalledInSubdirectory() {
		return ! empty( $this->getSubdirectory() );
	}

	/**
	 * @return string
	 */
	private function getSubdirectory() {
		$url       = get_option( 'home' );
		$home_path = trim( parse_url( $url, PHP_URL_PATH ), '/' );

		return $home_path;
	}
}
