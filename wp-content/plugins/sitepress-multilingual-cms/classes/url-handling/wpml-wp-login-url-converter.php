<?php

namespace WPML\UrlHandling;

use WPML\API\Sanitize;
use WPML\Element\API\Languages;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\LIB\WP\Option;
use WPML\LIB\WP\User;

class WPLoginUrlConverter implements \IWPML_Action {
	const PRIORITY_AFTER_URL_FILTERS = 100;
	const SETTINGS_KEY               = 'wpml_login_page_translation';

	private $rewrite_rule_not_found;

	/** @var \WPML_URL_Converter $url_converter */
	private $url_converter;

	/** @var \SitePress $sitepress */
	private $sitepress;

	/**
	 * @param \WPML_URL_Converter $url_converter
	 * @param \SitePress          $sitepress
	 */
	public function __construct( $sitepress, $url_converter ) {
		$this->rewrite_rule_not_found = false;
		$this->url_converter          = $url_converter;
		$this->sitepress              = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'site_url', [ $this, 'site_url' ], self::PRIORITY_AFTER_URL_FILTERS, 3 );
		add_filter( 'login_url', [ $this, 'convert_url' ] );

		add_filter( 'register_url', [ $this, 'convert_url' ] );
		add_filter( 'lostpassword_url', [ $this, 'convert_url' ] );
		add_filter( 'request', [ $this, 'on_request' ] );
		add_filter(
			'wpml_get_language_from_url',
			[
				$this,
				'wpml_login_page_language_from_url',
			],
			self::PRIORITY_AFTER_URL_FILTERS,
			2
		);
		add_filter( 'registration_redirect', [ $this, 'filter_redirect_with_lang' ] );
		add_filter( 'lostpassword_redirect', [ $this, 'filter_redirect_with_lang' ] );

		if ( self::isEnabled() ) {
			add_filter( 'logout_url', [ $this, 'convert_user_logout_url' ] );
			add_filter( 'logout_redirect', [ $this, 'convert_default_redirect_url' ], 2, 2 );
		}

		add_action( 'generate_rewrite_rules', [ $this, 'generate_rewrite_rules' ] );
		add_action( 'login_init', [ $this, 'redirect_to_login_url_with_lang' ] );

		if ( is_multisite() ) {
			add_filter( 'network_site_url', [ $this, 'site_url' ], self::PRIORITY_AFTER_URL_FILTERS, 3 );
			add_filter( 'wp_signup_location', [ $this, 'convert_url' ] );
			add_action( 'signup_hidden_fields', [ $this, 'add_signup_language_field' ] );

		}
	}

	/**
	 * Converts the logout URL to be translated.
	 *
	 * @param string $url
	 * @return string
	 */
	public function convert_user_logout_url( $url ) {
		$current_user_id = User::getCurrentId();
		if ( $current_user_id ) {
			$user_locale = User::getMetaSingle( $current_user_id, 'locale' );
			if ( ! empty( $user_locale ) ) {
				$language_code = $this->sitepress->get_language_code_from_locale( $user_locale );
				return $this->url_converter->convert_url( $url, $language_code );
			}
		}
		return $url;
	}

	public function add_signup_language_field() {
		echo "<input type='hidden' name='lang' value='" . $this->sitepress->get_current_language() . "' />";
	}

	public function redirect_to_login_url_with_lang() {
		$sitePath              = Obj::propOr( '', 'path', parse_url( site_url() ) );
		/** @var string $requestUriWithoutPath */
		$requestUriWithoutPath = Str::trimPrefix( (string) $sitePath, (string) $_SERVER['REQUEST_URI'] );

		$converted_url = site_url( $requestUriWithoutPath, 'login' );
		if (
			! is_multisite()
			&& $converted_url != site_url( $requestUriWithoutPath )
			&& wp_redirect( $converted_url, 302, 'WPML' )
		) {
			exit;
		}
	}

	public function generate_rewrite_rules() {
		global $wp_rewrite;

		$language_rules = wpml_collect( Languages::getActive() )->mapWithKeys(
			function ( $lang ) {
				return [ $lang['code'] . '/wp-login.php' => 'wp-login.php' ];
			}
		);

		$wp_rewrite->non_wp_rules = array_merge( $language_rules->toArray(), $wp_rewrite->non_wp_rules );
	}

	/**
	 * Converts the redirected string if it's the default one.
	 *
	 * @param string $redirect_to
	 * @param string $requested_redirect_to
	 * @return string
	 */
	public function convert_default_redirect_url( $redirect_to, $requested_redirect_to ) {
		if ( '' === $requested_redirect_to ) {
			return $this->convert_url( $redirect_to );
		}

		return $redirect_to;
	}

	public function filter_redirect_with_lang( $redirect_to ) {
		if ( ! $redirect_to && Obj::prop( 'lang', $_GET ) ) {
			$redirect_to = site_url( 'wp-login.php?checkemail=confirm', 'login' );
		}

		return $redirect_to;
	}

	public function wpml_login_page_language_from_url( $language, $url ) {
		if ( $this->is_wp_login_url( $url ) ) {
			if ( isset( $_GET['lang'] ) && $_GET['lang'] != $language ) {
				return Sanitize::stringProp( 'lang', $_GET );
			}
			if ( is_multisite() && isset( $_POST['lang'] ) && $_POST['lang'] != $language ) {
				return Sanitize::stringProp( 'lang', $_POST );
			}
		}

		return $language;
	}

	public function site_url( $url, $path, $scheme ) {
		if ( $scheme === 'login_post' || $scheme === 'login' || $this->should_convert_url_for_multisite( $url ) ) {
			$url = $this->convert_url( $url );
		}

		return $url;
	}

	public function convert_url( $url ) {
		$lang_param = $this->get_language_param_for_convert_url();
		if ( $lang_param ) {
			return add_query_arg( 'lang', $lang_param, $url );
		}

		return $this->url_converter->convert_url( $url );
	}

	public function on_request( $query_vars ) {
		if ( Relation::propEq( 'name', 'wp-login.php', $query_vars ) ) {
			$this->rewrite_rule_not_found = true;
		} else {
			$this->rewrite_rule_not_found = false;
		}

		if ( $this->rewrite_rule_not_found && $this->is_wp_login_action() ) {
			$current_url = site_url( $_SERVER['REQUEST_URI'], 'login' );
			$redirect_to = $this->remove_language_directory_from_url( $current_url );

			if (
				$redirect_to !== $current_url
				&& wp_redirect( $redirect_to, 302, 'WPML' )
			) {
				exit;
			}
		}

		return $query_vars;
	}

	/**
	 * @param bool $validateOrRollback - If true, it will be validated that the translated Login URL is accessible or rollback.
	 * @return void
	 */
	public static function enable( $validateOrRollback = false ) {
		self::saveState( true, $validateOrRollback );
	}

	public static function disable() {
		self::saveState( false );
	}

	/**
	 * @return bool
	 */
	public static function isEnabled() {
		return Option::getOr( self::SETTINGS_KEY, false );
	}

	/**
	 * @param bool $state
	 * @param bool $validate - if true, will validate the change or undo it.
	 *
	 */
	public static function saveState( $state, $validate = false ) {
		Option::update( self::SETTINGS_KEY, $state );
		WPLoginUrlConverterRules::markRulesForUpdating( $validate );
	}

	private function should_convert_url_for_multisite( $url ) {
		return is_multisite() && Str::includes( 'wp-activate.php', $url );
	}

	private function is_wp_login_url( $url ) {
		return Str::includes( 'wp-login.php', $url )
		       || Str::includes( 'wp-signup.php', $url )
		       || Str::includes( 'wp-activate.php', $url );

	}

	private function is_wp_login_action() {
		$actions = wpml_collect(
			[
				'confirm_admin_email',
				'postpass',
				'logout',
				'lostpassword',
				'retrievepassword',
				'resetpass',
				'rp',
				'register',
				'login',
				'confirmaction',
			]
		);

		return $actions->contains( Obj::prop( 'action', $_GET ) );
	}

	private function get_language_param_for_convert_url() {
		if ( isset( $_GET['lang'] ) ) {
			return Sanitize::stringProp( 'lang', $_GET );
		}
		if ( is_multisite() && isset( $_POST['lang'] ) ) {
			return Sanitize::stringProp( 'lang', $_POST );
		}
		if ( $this->rewrite_rule_not_found || $this->is_subdomain_multisite() ) {
			return $this->sitepress->get_current_language();
		}

		return null;
	}

	private function is_subdomain_multisite() {
		return is_multisite() && defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL;
	}

	private function remove_language_directory_from_url( $url ) {
		$lang      = $this->get_language_param_for_convert_url();
		$url_parts = wpml_parse_url( $url );
		if ( $lang && Str::includes( '/' . $lang . '/', $url_parts['path'] ) ) {
			$url = Str::replace( '/' . $lang . '/', '/', $url );
		}

		return $url;
	}
}
