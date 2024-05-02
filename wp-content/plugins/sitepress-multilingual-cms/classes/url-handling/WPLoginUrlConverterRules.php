<?php

namespace WPML\UrlHandling;

use WPML\LIB\WP\Option;
use function WPML\Container\make;

class WPLoginUrlConverterRules implements \IWPML_Action {

	const MARKED_FOR_UPDATE_AND_VALIDATE_OR_ROLLBACK = 3;
	const MARKED_FOR_UPDATE = true;
	const UNMARKED = false;

	const SKIP_SAVING_LANG_IN_COOKIES_KEY = 'skip_saving_language_cookie';

	const UPDATE_RULES_KEY = 'wpml_login_page_translation_update_rules';

	public function add_hooks() {
		/**
		 * Filter hook that checks existence of skip_saving_language_cookie key in $_GET and whether its value is 'true'.
		 * According to that we make decision to save language code in cookies or not
		 */
		add_filter( 'wpml_should_skip_saving_language_in_cookies', function ( $forceSkipSavingLangInCookies ) {
			if ( $forceSkipSavingLangInCookies ) {
				return true;
			}

			return isset( $_GET[ WPLoginUrlConverterRules::SKIP_SAVING_LANG_IN_COOKIES_KEY ] )
			       && $_GET[ WPLoginUrlConverterRules::SKIP_SAVING_LANG_IN_COOKIES_KEY ] === 'true';
		} );
		
		if ( Option::getOr( self::UPDATE_RULES_KEY, self::UNMARKED ) ) {
			add_filter( 'init', [ self::class, 'update' ] );
		}
	}

	public static function markRulesForUpdating( $verify = false ) {
		Option::update(
			self::UPDATE_RULES_KEY,
			$verify ? self::MARKED_FOR_UPDATE_AND_VALIDATE_OR_ROLLBACK : self::MARKED_FOR_UPDATE
		);
	}

	public static function update() {
		global $wp_rewrite;

		if ( ! function_exists( 'save_mod_rewrite_rules' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$wp_rewrite->rewrite_rules();
		save_mod_rewrite_rules();
		$wp_rewrite->flush_rules( false );

		$needsValidation = self::MARKED_FOR_UPDATE_AND_VALIDATE_OR_ROLLBACK === (int) Option::get( self::UPDATE_RULES_KEY );
		Option::update( self::UPDATE_RULES_KEY, self::UNMARKED );

		if ( $needsValidation ) {
			static::validateOrDisable();
		}
	}

	/**
	 * Validates that the Translated Login URL is accessible.
	 * Used to validate the setting when enabled by default.
	 */
	public static function validateOrDisable() {
		$translationLangs = \WPML\Setup\Option::getTranslationLangs();

		if ( empty( $translationLangs ) ) {
			return;
		}

		/** @var \WPML_URL_Converter $urlConverter */
		$urlConverter = make( \WPML_URL_Converter::class );
		$newUrl       = $urlConverter->convert_url( wp_login_url(), $translationLangs[0] );

		/**
		 * We pass SKIP_SAVING_LANG_IN_COOKIES_KEY to avoid saving the language code in $sitepress and cookies in this case
		 * @see https://onthegosystems.myjetbrains.com/youtrack/issue/wpmldev-1544
		 */
		$loginResponseCode = wp_remote_retrieve_response_code( wp_remote_get( $newUrl, [
			'body' => [
				self::SKIP_SAVING_LANG_IN_COOKIES_KEY => 'true'
			]
		] ) );

		if ( 200 !== $loginResponseCode ) {
			WPLoginUrlConverter::disable();
		}
	}
}
