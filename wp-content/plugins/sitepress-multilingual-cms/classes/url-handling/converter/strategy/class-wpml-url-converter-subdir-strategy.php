<?php

class WPML_URL_Converter_Subdir_Strategy extends WPML_URL_Converter_Abstract_Strategy {
	/** @var bool */
	private $use_directory_for_default_lang;

	/** @var array copy of $sitepress->get_settings( 'urls' ) */
	private $urls_settings;

	/** @var string|bool */
	private $root_url;

	/** @var array map of wpml codes to custom codes*/
	private $language_codes_map;
	private $language_codes_reverse_map;

	/** @var bool */
	private $is_rest_request;

	/**
	 * @param bool   $use_directory_for_default_lang
	 * @param string $default_language
	 * @param array  $active_languages
	 * @param array  $urls_settings
	 */
	public function __construct(
		$use_directory_for_default_lang,
		$default_language,
		$active_languages,
		$urls_settings
	) {
		parent::__construct( $default_language, $active_languages );
		$this->use_directory_for_default_lang = (bool) $use_directory_for_default_lang;
		$this->urls_settings                  = $urls_settings;

		$this->language_codes_map = array_combine( $active_languages, $active_languages );
		$this->language_codes_map = apply_filters( 'wpml_language_codes_map', $this->language_codes_map );

		$this->language_codes_reverse_map = array_flip( $this->language_codes_map );
	}

	public function get_lang_from_url_string( $url ) {
		$url_path = $this->get_url_path( wpml_strip_subdir_from_url( $url ) );
		$lang     = $this->extract_lang_from_url_path( $url_path );

		if ( $lang && in_array( $lang, $this->active_languages, true ) ) {
			return $lang;
		}
		
		return $this->use_directory_for_default_lang ? null : $this->default_language;
	}

	public function validate_language( $language, $url ) {
		if ( ! ( null === $language && $this->use_directory_for_default_lang && ! $this->get_url_helper()->is_url_admin( $url ) ) ) {
			$language = parent::validate_language( $language, $url );
		}

		return $language;
	}

	public function convert_url_string( $source_url, $code ) {
		if ( $this->is_root_url( $source_url ) || $this->skip_convert_url_string( $source_url, $code ) ) {
			return $source_url;
		}

		$source_url = $this->filter_source_url( $source_url );

		$absolute_home_url = trailingslashit( preg_replace( '#^(http|https)://#', '', $this->get_url_helper()->get_abs_home() ) );
		$absolute_home_url = strpos( $source_url, $absolute_home_url ) === false ? trailingslashit( get_option( 'home' ) ) : $absolute_home_url;

		$current_language = $this->get_lang_from_url_string( $source_url );
		$code             = $this->get_language_of_current_dir( $code, '' );
		$current_language = $this->get_language_of_current_dir( $current_language, '' );

		$code             = isset( $this->language_codes_map[ $code ] ) ? $this->language_codes_map[ $code ] : $code;
		$current_language = isset( $this->language_codes_map[ $current_language ] ) ? $this->language_codes_map[ $current_language ] : $current_language;

		$source_url = str_replace(
			[
				trailingslashit( $absolute_home_url . $current_language ),
				'/' . $code . '//',
			],
			[
				$code ? ( $absolute_home_url . $code . '/' ) : trailingslashit( $absolute_home_url ),
				'/' . $code . '/',
			],
			$source_url
		);

		return $this->slash_helper->maybe_user_trailingslashit( $source_url );
	}

	public function convert_admin_url_string( $source_url, $lang ) {
		return $source_url; // Admin strings should not be converted with language in directories
	}

	/**
	 * @param string $url
	 * @param string $language
	 *
	 * @return string
	 */
	public function get_home_url_relative( $url, $language ) {
		$language = $this->get_language_of_current_dir( $language, '' );
		$language = isset( $this->language_codes_map[ $language ] ) ? $this->language_codes_map[ $language ] : $language;

		if ( $language ) {
			$parts = parse_url( get_option( 'home' ) );
			$path  = isset( $parts['path'] ) ? $parts['path'] : '';
			$url   = preg_replace( '@^' . $path . '@', '', $url );

			return rtrim( $path, '/' ) . '/' . $language . $url;
		} else {
			return $url;
		}
	}

	/**
	 * Will return true if root URL or child of root URL
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	private function is_root_url( $url ) {
		$result  = false;

		if ( isset( $this->urls_settings['root_page'], $this->urls_settings['show_on_root'] ) &&
		     'page' === $this->urls_settings['show_on_root'] &&
			! empty( $this->urls_settings['directory_for_default_language'] )
		) {

			$root_url = $this->get_root_url();
			if ( $root_url ) {
				$result = strpos( trailingslashit( $url ), $root_url ) === 0;
			}
		}

		return $result;
	}

	/**
	 * @return string|bool
	 */
	private function get_root_url() {
		if ( null === $this->root_url ) {
			$root_post = get_post( $this->urls_settings['root_page'] );

			if ( $root_post ) {
				$this->root_url = trailingslashit( $this->get_url_helper()->get_abs_home() ) . $root_post->post_name;
				$this->root_url = trailingslashit( $this->root_url );
			} else {
				$this->root_url = false;
			}
		}

		return $this->root_url;
	}

	/**
	 * @param string $source_url
	 *
	 * @return string
	 */
	private function filter_source_url( $source_url ) {
		if ( false === strpos( $source_url, '?' ) ) {
			$source_url = trailingslashit( $source_url );
		} elseif ( false !== strpos( $source_url, '?' ) && false === strpos( $source_url, '/?' ) ) {
			$source_url = str_replace( '?', '/?', $source_url );
		}

		return $source_url;
	}

	/**
	 * @param $url
	 *
	 * @return string
	 */
	private function get_url_path( $url ) {
		if ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ) {
			$url_path = wpml_parse_url( $url, PHP_URL_PATH );
		} else {
			$pathparts = array_filter( explode( '/', $url ) );
			if ( count( $pathparts ) > 1 ) {
				unset( $pathparts[0] );
				$url_path = implode( '/', $pathparts );
			} else {
				$url_path = $url;
			}
		}

		return $url_path;
}

	/**
	 * @param string $url_path
	 *
	 * @return string
	 */
	private function extract_lang_from_url_path( $url_path ) {
		$fragments = array_filter( (array) explode( '/', $url_path ) );
		$lang      = array_shift( $fragments );

		$lang_get_parts = explode( '?', $lang );
		$lang           = $lang_get_parts[0];

		return isset( $this->language_codes_reverse_map[ $lang ] ) ? $this->language_codes_reverse_map[ $lang ] : $lang;
	}

	/**
	 * @param string      $language_code
	 * @param null|string $value_if_default_language
	 *
	 * @return string|null
	 */
	private function get_language_of_current_dir( $language_code, $value_if_default_language = null ) {
		if ( ! $this->use_directory_for_default_lang && $language_code === $this->default_language ) {
			return $value_if_default_language;
		}

		return $language_code;
	}

}
