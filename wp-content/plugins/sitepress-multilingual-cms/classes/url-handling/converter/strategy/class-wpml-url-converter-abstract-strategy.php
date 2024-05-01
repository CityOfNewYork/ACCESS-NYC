<?php

use WPML\FP\Str;

abstract class WPML_URL_Converter_Abstract_Strategy implements IWPML_URL_Converter_Strategy {
	protected $absolute_home;

	protected $default_language;
	protected $active_languages;

	protected $cache;

	/**
	 * @var WPML_URL_Converter_Url_Helper
	 */
	protected $url_helper;

	/**
	 * @var WPML_URL_Converter_Lang_Param_Helper
	 */
	protected $lang_param;

	/**
	 * @var WPML_Slash_Management
	 */
	protected $slash_helper;

	/**
	 * @var WP_Rewrite
	 */
	protected $wp_rewrite;

	/**
	 * @param string                     $default_language
	 * @param array<string>              $active_languages
	 * @param WP_Rewrite|null            $wp_rewrite
	 * @param WPML_Slash_Management|null $splash_helper
	 */
	public function __construct( $default_language, $active_languages, $wp_rewrite = null, $splash_helper = null ) {
		$this->default_language = $default_language;
		$this->active_languages = $active_languages;

		$this->lang_param   = new WPML_URL_Converter_Lang_Param_Helper( $active_languages );
		$this->slash_helper = $splash_helper ?: new WPML_Slash_Management();

		if ( ! $wp_rewrite ) {
			global $wp_rewrite;
		}
		$this->wp_rewrite = $wp_rewrite;
	}

	public function validate_language( $language, $url ) {
		if ( Str::includes( 'wp-login.php', $_SERVER['REQUEST_URI'] ) ) {
			return $language;
		}

		return in_array( $language, $this->active_languages, true )
			   || 'all' === $language && $this->get_url_helper()->is_url_admin( $url ) ? $language : $this->get_default_language();
	}

	/**
	 * @param WPML_URL_Converter_Url_Helper $url_helper
	 */
	public function set_url_helper( WPML_URL_Converter_Url_Helper $url_helper ) {
		$this->url_helper = $url_helper;
	}

	/**
	 * @return WPML_URL_Converter_Url_Helper
	 */
	public function get_url_helper() {
		if ( ! $this->url_helper ) {
			$this->url_helper = new WPML_URL_Converter_Url_Helper();
		}

		return $this->url_helper;
	}

	/**
	 * @param WPML_URL_Converter_Lang_Param_Helper $lang_param
	 */
	public function set_lang_param( WPML_URL_Converter_Lang_Param_Helper $lang_param ) {
		$this->lang_param = $lang_param;
	}

	/**
	 * @param WPML_Slash_Management $slash_helper
	 */
	public function set_slash_helper( WPML_Slash_Management $slash_helper ) {
		$this->slash_helper = $slash_helper;
	}

	private function get_default_language() {
		if ( $this->default_language ) {
			return $this->default_language;
		} else {
			return icl_get_setting( 'default_language' );
		}
	}

	public function fix_trailingslashit( $source_url ) {
		return $source_url;
	}

	public function skip_convert_url_string( $source_url, $lang_code ) {
		/**
		 * Allows plugins to skip url conversion.
		 *
		 * @since 4.3
		 *
		 * @param bool $skip
		 * @param string $source_url
		 * @param string $lang_code
		 * @return bool
		 */
		return apply_filters( 'wpml_skip_convert_url_string', false, $source_url, $lang_code );
	}

	public function use_wp_login_url_converter() {
		return false;
	}
}
