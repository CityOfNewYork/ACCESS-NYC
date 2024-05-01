<?php

namespace WPML\AdminLanguageSwitcher;

use WPML\Element\API\Languages;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\LIB\WP\Option;
use WPML\UrlHandling\WPLoginUrlConverter;

class AdminLanguageSwitcher implements \IWPML_Frontend_Action {

	const LANGUAGE_SWITCHER_KEY = 'wpml_show_login_page_language_switcher';

	public function add_hooks() {
		add_action( 'login_footer', [ $this, 'triggerDropdown' ] );
		add_action( 'plugins_loaded', [ $this, 'maybeSaveNewLanguage' ] );
	}

	public function maybeSaveNewLanguage() {
		if ( ! $this->isLanguageSwitcherShown() ) {
			return;
		}

		$selectedLocale = $this->getSelectedLocale();

		if ( $selectedLocale ) {
			$languageCode = Languages::localeToCode( $selectedLocale );
			if ( ! is_string( $languageCode ) ) {
				return;
			}

			$secure       = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
			setcookie( 'wp-wpml_login_lang', $languageCode, time() + 120, COOKIEPATH, COOKIE_DOMAIN, $secure );
			setcookie( 'wp_lang', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, $secure );

			global $sitepress;
			wp_safe_redirect( $this->prepareRedirectLink( $sitepress, $languageCode ) );
		}

	}

	public function triggerDropdown() {
		if ( ! $this->isLanguageSwitcherShown() ) {
			return;
		}

		wp_register_style(
			'wpml-login-language-switcher',
			ICL_PLUGIN_URL . '/res/css/login-language-switcher.css'
		);
		wp_enqueue_style( 'wpml-login-language-switcher' );

		$selectedLocale = determine_locale();

		$languages = wpml_collect( Languages::getActive() );

		$prepareOptions = function ( $languages ) use ( $selectedLocale ) {
			return $languages->sortBy( function ( $language ) use ( $selectedLocale ) {
				return $language['default_locale'] !== $selectedLocale;
			} )
			                 ->map( Obj::addProp( 'selected', Relation::propEq( 'default_locale', $selectedLocale ) ) )
			                 ->map( function ( $language ) {

				                 return sprintf( '<option value="%s" lang="%s" %s>%s</option>',
					                 esc_attr( Obj::prop( 'default_locale', $language ) ),
					                 esc_attr( Obj::prop( 'code', $language ) ),
					                 esc_attr( Obj::prop( 'selected', $language ) ? 'selected' : '' ),
					                 esc_html( Obj::prop( 'native_name', $language ) )
				                 );
			                 } );
		};

		AdminLanguageSwitcherRenderer::render( $prepareOptions( $languages )->all() );
	}

	private function isOnWpLoginPage( $url ) {
		return Str::includes( 'wp-login.php', $url )
		       || Str::includes( 'wp-signup.php', $url )
		       || Str::includes( 'wp-activate.php', $url );
	}

	/**
	 * @return string|false
	 */
	private function getSelectedLocale() {
		return Maybe::of( $_GET )
			->map( Obj::prop('wpml_lang' ) )
			->map( 'sanitize_text_field' )
			->getOrElse( false );
	}

	/**
	 * @return bool
	 */
	private function isLanguageSwitcherShown() {
		return $this->isOnWpLoginPage( site_url( $_SERVER['REQUEST_URI'], 'login' ) ) && WPLoginUrlConverter::isEnabled() && self::isEnabled();
	}

	/**
	 * @param $sitepress
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function prepareRedirectLink( $sitepress, $languageCode ) {
		$redirectTo = $sitepress->convert_url( site_url( 'wp-login.php' ), $languageCode );

		$redirectToParam = Maybe::of( $_GET )
		                        ->map( Obj::prop( 'redirect_to' ) )
		                        ->map( 'esc_url_raw' )
		                        ->getOrElse( false );

		$action = Maybe::of( $_GET )
		               ->map( Obj::prop( 'action' ) )
		               ->map( 'esc_attr' )
		               ->getOrElse( false );

		if ( $redirectToParam ) {
			$redirectTo = add_query_arg( 'redirect_to', $redirectToParam, $redirectTo );
		}

		if ( $action ) {
			$redirectTo = add_query_arg( 'action', $action, $redirectTo );
		}

		return $redirectTo;
	}

	/**
	 * @param bool $state
	 */
	public static function saveState( $state ) {
		Option::updateWithoutAutoLoad( self::LANGUAGE_SWITCHER_KEY, $state );
	}

	/**
	 * @return bool
	 */
	public static function isEnabled() {
		return Option::getOr( self::LANGUAGE_SWITCHER_KEY, true );
	}

	public static function enable() {
		self::saveState( true );
	}

	public static function disable() {
		self::saveState( false );
	}
}
