<?php

/**
 * Class WPML_Canonicals_Hooks
 */
class WPML_Canonicals_Hooks {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var  WPML_URL_Converter $url_converter */
	private $url_converter;

	/** @var callable $is_current_request_root_callback */
	private $is_current_request_root_callback;

	/**
	 * WPML_Canonicals_Hooks constructor.
	 *
	 * @param SitePress          $sitepress
	 * @param WPML_URL_Converter $url_converter
	 * @param callable           $is_current_request_root_callback
	 */
	public function __construct( SitePress $sitepress, WPML_URL_Converter $url_converter, $is_current_request_root_callback ) {
		$this->sitepress                        = $sitepress;
		$this->url_converter                    = $url_converter;
		$this->is_current_request_root_callback = $is_current_request_root_callback;
	}

	public function add_hooks() {
		$urls             = $this->sitepress->get_setting( 'urls' );
		$lang_negotiation = (int) $this->sitepress->get_setting( 'language_negotiation_type' );

		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === $lang_negotiation
			 && ! empty( $urls['directory_for_default_language'] )
		) {
			add_action( 'template_redirect', array( $this, 'redirect_pages_from_root_to_default_lang_dir' ) );
			add_action( 'template_redirect', [ $this, 'redirectArchivePageToDefaultLangDir' ] );
		} elseif ( WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER === $lang_negotiation ) {
			add_filter( 'redirect_canonical', array( $this, 'prevent_redirection_with_translated_paged_content' ) );
		}

		if ( WPML_LANGUAGE_NEGOTIATION_TYPE_DIRECTORY === $lang_negotiation ) {
			add_filter( 'redirect_canonical', [ $this, 'prevent_redirection_of_frontpage_on_secondary_language' ], 10, 2 );
		}
	}

	public function redirect_pages_from_root_to_default_lang_dir() {
		global $wp_query;

		if ( ! ( ( $wp_query->is_page() || $wp_query->is_posts_page ) && ! call_user_func( $this->is_current_request_root_callback ) ) ) {
			return;
		}

		$lang           = $this->sitepress->get_current_language();
		$current_uri    = $_SERVER['REQUEST_URI'];
		$abs_home       = $this->url_converter->get_abs_home();
		$install_subdir = wpml_parse_url( $abs_home, PHP_URL_PATH );

		$actual_uri = is_string( $install_subdir )
			? preg_replace( '#^' . $install_subdir . '#', '', $current_uri )
			: $current_uri;
		$actual_uri = '/' . ltrim( $actual_uri, '/' );

		if ( 0 === strpos( $actual_uri, '/' . $lang ) ) {
			return;
		}

		$canonical_uri = is_string( $install_subdir )
			? trailingslashit( $install_subdir ) . $lang . $actual_uri
			: '/' . $lang . $actual_uri;
		$canonical_uri = user_trailingslashit( $canonical_uri );
		$this->redirectTo( $canonical_uri );
	}

	/**
	 * When :
	 *
	 * The current template that user tries to load is for archive page
	 *
	 * And the default language in a directory mode is active
	 *
	 * And the language code is not present in the current request URI
	 *
	 * Then: We make a redirect to the proper URI that contains the default language code as directory.
	 */
	public function redirectArchivePageToDefaultLangDir() {
		$isValidForRedirect = is_archive() && ! call_user_func( $this->is_current_request_root_callback );
		if ( ! $isValidForRedirect ) {
			return;
		}

		$currentUri = $_SERVER['REQUEST_URI'];
		$lang       = $this->sitepress->get_current_language();

		$home_url        = rtrim( $this->url_converter->get_abs_home(), '/' );
		$parsed_site_url = wp_parse_url( $home_url );

		if ( isset( $parsed_site_url['path'] ) ) {
			// Cater for site installed in sub-folder.
			$path = $parsed_site_url['path'];

			if ( ! empty( $path ) && strpos( $currentUri, $path ) === 0 ) {
				$currentUri = substr( $currentUri, strlen( $path ) );
			}
		}

		if ( 0 !== strpos( $currentUri, '/' . $lang ) ) {
			$canonicalUri = user_trailingslashit(
				$home_url . '/' . $lang . $currentUri
			);

			$this->redirectTo( $canonicalUri );
		}
	}

	private function redirectTo( $uri ) {
		$this->sitepress->get_wp_api()->wp_safe_redirect( $uri, 301 );
	}

	/**
	 * First we have to check if we are on front page and the current language is different than the default one.
	 * If not, then we don't have to do anything -> return the $redirect_url.
	 *
	 * Next we check if the $redirect_url is the same as the $requested_url + '/'.
	 * Then, we have to check if the permalink structure does not have the trailing slash.
	 *
	 * If both conditions are true, then we return false, so the redirection will not happen.
	 *
	 * @param string $redirect_url
	 * @param string $requested_url
	 *
	 * @return string|false
	 */
	public function prevent_redirection_of_frontpage_on_secondary_language( $redirect_url, $requested_url ) {
		if ( ! is_front_page() || $this->sitepress->get_current_language() === $this->sitepress->get_default_language() ) {
			return $redirect_url;
		}

		if ( substr( get_option( 'permalink_structure' ), - 1 ) !== '/' ) {
			if ( $redirect_url === $requested_url . '/' ) {
				return false;
			}

			/**
			 * Notice that the permalink structure does not have the trailing slash in this place.
			 *
			 * If a user requests site like `http://www.develop.test/fr` while home_url is set to `http://develop.test`,
			 * then the $redirect_url will be `http://develop.test/fr/` and the $requested_url will be `http://www.develop.test/fr`.
			 * The trailing slash is added to $redirect_url by WP function `redirect_canonical` and is not desired.
			 *
			 * If we did not remove it, WP function `redirect_canonical` would redirect to `http://develop.test/fr` again.
			 * Its internal safety mechanism does not allow multiple redirects. Therefore, the whole redirect would be skipped.
			 */
			$redirect_url = untrailingslashit( $redirect_url );
		}

		return $redirect_url;
	}

	/**
	 * @param string $redirect_url
	 *
	 * @return string|false
	 */
	public function prevent_redirection_with_translated_paged_content( $redirect_url ) {
		if ( ! is_singular() || ! isset( $_GET['lang'] ) ) {
			return $redirect_url;
		}

		$page = (int) get_query_var( 'page' );

		if ( $page < 2 ) {
			return $redirect_url;
		}

		return false;
	}
}
