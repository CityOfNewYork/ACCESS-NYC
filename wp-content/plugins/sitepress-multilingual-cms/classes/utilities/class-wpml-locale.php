<?php

use WPML\Collect\Support\Collection;

class WPML_Locale {
	/**
	 * @var wpdb
	 */
	private $wpdb;
	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var  string $locale
	 */
	private $locale;
	private $locale_cache;

	/** @var Collection $all_locales */
	private $all_locales;

	/**
	 * WPML_Locale constructor.
	 *
	 * @param wpdb $wpdb
	 * @param SitePress $sitepress
	 * @param string $locale
	 */
	public function __construct( wpdb &$wpdb, SitePress &$sitepress, &$locale ) {
		$this->wpdb         =& $wpdb;
		$this->sitepress    =& $sitepress;
		$this->locale       =& $locale;
		$this->locale_cache = null;
	}

	public function init() {
		if ( $this->language_needs_title_sanitization() ) {
			add_filter( 'sanitize_title', array( $this, 'filter_sanitize_title' ), 10, 2 );
		}
	}

	/**
	 * @see \Test_Admin_Settings::test_locale
	 * @fixme
	 * Due to the way these tests work (global state issues) I had to create this method
	 * to ensure we have full coverage of the code.
	 * This method shouldn't be used anywhere else and should be removed once tests are migrated
	 * to the new tests framework.
	 */
	public function reset_cached_data() {
		$this->locale_cache         = null;
		$this->all_locales          = null;
	}

	/**
	 * Hooked to 'sanitize_title' in case the user is using a language that has either German or Danish locale, to
	 * ensure that WP Core sanitization functions handle special chars accordingly.
	 *
	 * @param string $title
	 * @param string $raw_title
	 *
	 * @return string
	 */
	public function filter_sanitize_title( $title, $raw_title ) {
		if ( $title !== $raw_title ) {
			remove_filter( 'sanitize_title', array( $this, 'filter_sanitize_title' ), 10 );
			$chars                            = array();
			$chars[ chr( 195 ) . chr( 132 ) ] = 'Ae';
			$chars[ chr( 195 ) . chr( 133 ) ] = 'Aa';
			$chars[ chr( 195 ) . chr( 134 ) ] = 'Ae';
			$chars[ chr( 195 ) . chr( 150 ) ] = 'Oe';
			$chars[ chr( 195 ) . chr( 152 ) ] = 'Oe';
			$chars[ chr( 195 ) . chr( 156 ) ] = 'Ue';
			$chars[ chr( 195 ) . chr( 159 ) ] = 'ss';
			$chars[ chr( 195 ) . chr( 164 ) ] = 'ae';
			$chars[ chr( 195 ) . chr( 165 ) ] = 'aa';
			$chars[ chr( 195 ) . chr( 166 ) ] = 'ae';
			$chars[ chr( 195 ) . chr( 182 ) ] = 'oe';
			$chars[ chr( 195 ) . chr( 184 ) ] = 'oe';
			$chars[ chr( 195 ) . chr( 188 ) ] = 'ue';
			$title                            = sanitize_title( strtr( $raw_title, $chars ) );
			add_filter( 'sanitize_title', array( $this, 'filter_sanitize_title' ), 10, 2 );
		}

		return $title;
	}

	/**
	 * @return bool|mixed
	 */
	public function locale() {
		if ( ! $this->locale_cache ) {
			add_filter( 'language_attributes', array( $this, '_language_attributes' ) );

			$wp_api  = $this->sitepress->get_wp_api();
			$is_ajax = $wp_api->is_ajax();
			if ( $is_ajax && isset( $_REQUEST['action'], $_REQUEST['lang'] ) ) {
				$locale_lang_code = $_REQUEST['lang'];
			} elseif ( $wp_api->is_admin()
			           && ( ! $is_ajax
			                || $this->sitepress->check_if_admin_action_from_referer() )
			) {
				$locale_lang_code = $this->sitepress->user_lang_by_authcookie();
			} else {
				$locale_lang_code = $this->sitepress->get_current_language();
			}
			$locale = $this->get_locale( $locale_lang_code );

			if ( did_action( 'plugins_loaded' ) ) {
				$this->locale_cache = $locale;
			}

			return $locale;
		}

		return $this->locale_cache;
	}

	/**
	 * @param string $code
	 *
	 * @return false|string
	 */
	public function get_locale( $code ) {
		if ( ! $code ) {
			return false;
		}

		return $this->get_all_locales()->get( $code, $code );
	}

	/**
	 * @return Collection
	 */
	public function get_all_locales() {
		if ( ! $this->all_locales ) {
			$sql = "
				SELECT
					l.code,
					m.locale,
					l.default_locale
				FROM {$this->wpdb->prefix}icl_languages AS l
				LEFT JOIN {$this->wpdb->prefix}icl_locale_map AS m ON m.code = l.code
			";

			$this->all_locales = wpml_collect( $this->wpdb->get_results( $sql ) )
				->mapWithKeys(
					function( $row ) {
						if ( $row->locale ) {
							$locale = $row->locale;
						} elseif ( $row->default_locale ) {
							$locale = $row->default_locale;
						} else {
							$locale = $row->code;
						}

						return [ $row->code => $locale ];
					}
				);
		}

		return $this->all_locales;
	}

	public function switch_locale( $lang_code = false ) {
		global $l10n;
		static $original_l10n;
		if ( ! empty( $lang_code ) ) {
			$original_l10n = isset( $l10n['sitepress'] ) ? $l10n['sitepress'] : null;
			if ( $original_l10n !== null ) {
				unset( $l10n['sitepress'] );
			}
			load_textdomain( 'sitepress',
				WPML_PLUGIN_PATH . '/locale/sitepress-' . $this->get_locale( $lang_code ) . '.mo' );
		} else { // switch back
			$l10n['sitepress'] = $original_l10n;
		}
	}

	public function get_locale_file_names() {
		$locales = array();
		$res     = $this->wpdb->get_results( "
			SELECT lm.code, locale
			FROM {$this->wpdb->prefix}icl_locale_map lm JOIN {$this->wpdb->prefix}icl_languages l ON lm.code = l.code AND l.active=1" );
		foreach ( $res as $row ) {
			$locales[ $row->code ] = $row->locale;
		}

		return $locales;
	}

	private function language_needs_title_sanitization() {
		$lang_needs_filter = array( 'de_DE', 'da_DK' );
		$current_lang      = $this->sitepress->get_language_details( $this->sitepress->get_current_language() );
		$needs_filter      = false;

		if ( ! isset( $current_lang['default_locale'] ) ) {
			return $needs_filter;
		}

		if ( in_array( $current_lang['default_locale'], $lang_needs_filter, true ) ) {
			$needs_filter = true;
		}

		return $needs_filter;
	}

	function _language_attributes( $latr ) {

		return preg_replace(
			'#lang="([a-z]+)"#i',
			'lang="' . str_replace( '_', '-', $this->locale ) . '"',
			$latr );
	}

	/**
	 * @return WPML_Locale
	 */
	public static function get_instance_from_sitepress() {
		/** SitePress $sitepress */
		global $sitepress;

		return $sitepress->get_wpml_locale();
	}
}