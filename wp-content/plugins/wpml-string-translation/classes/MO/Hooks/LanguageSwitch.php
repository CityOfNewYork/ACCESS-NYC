<?php

namespace WPML\ST\MO\Hooks;

use WPML\ST\MO\JustInTime\MOFactory;
use WPML\ST\MO\WPLocaleProxy;
use WPML\ST\Utils\LanguageResolution;

class LanguageSwitch implements \IWPML_Action {

	/** @var MOFactory $jit_mo_factory */
	private $jit_mo_factory;

	/** @var LanguageResolution $language_resolution */
	private $language_resolution;

	/** @var null|string $current_locale */
	private static $current_locale;

	/** @var array $globals_cache */
	private static $globals_cache = [];

	public function __construct(
		LanguageResolution $language_resolution,
		MOFactory $jit_mo_factory
	) {
		$this->language_resolution = $language_resolution;
		$this->jit_mo_factory      = $jit_mo_factory;
	}

	public function add_hooks() {
		add_action( 'wpml_language_has_switched', [ $this, 'languageHasSwitched' ] );
	}

	/** @param string $locale */
	private function setCurrentLocale( $locale ) {
		self::$current_locale = $locale;
	}

	/** @return string */
	public function getCurrentLocale() {
		return self::$current_locale;
	}

	public function languageHasSwitched() {
		$this->initCurrentLocale();
		$new_locale = $this->language_resolution->getCurrentLocale();
		$this->switchToLocale( $new_locale );
	}

	public function initCurrentLocale() {
		if ( ! $this->getCurrentLocale() ) {
			add_filter( 'locale', [ $this, 'filterLocale' ], PHP_INT_MAX );
			$this->setCurrentLocale( $this->language_resolution->getCurrentLocale() );
		}
	}

	/**
	 * This method will act as the WP Core function `switch_to_locale`,
	 * but in a more efficient way. It will avoid to instantly load
	 * the domains loaded in the previous locale. Instead, it will let
	 * the domains be loaded via the "just in time" function.
	 *
	 * @param string $new_locale
	 */
	public function switchToLocale( $new_locale ) {
		if ( $new_locale === $this->getCurrentLocale() ) {
			return;
		}

		$this->updateCurrentGlobalsCache();
		$this->changeWpLocale( $new_locale );
		$this->changeMoObjects( $new_locale );
		$this->setCurrentLocale( $new_locale );
	}

	/**
	 * @param string|null $locale
	 */
	public static function resetCache( $locale = null ) {
		self::$current_locale = $locale;
		self::$globals_cache = [];
	}

	/**
	 * We need to take a new copy of the current locale globals
	 * because some domains could have been added with the "just in time"
	 * mechanism.
	 */
	private function updateCurrentGlobalsCache() {
		$cache = [
			'wp_locale' => isset( $GLOBALS['wp_locale'] ) ? $GLOBALS['wp_locale'] : null,
			'l10n'      => isset( $GLOBALS['l10n'] ) ? (array) $GLOBALS['l10n'] : [],
		];

		self::$globals_cache[ $this->getCurrentLocale() ] = $cache;
	}

	/**
	 * @param string $new_locale
	 */
	private function changeWpLocale( $new_locale ) {
		if ( isset( self::$globals_cache[ $new_locale ]['wp_locale'] ) ) {
			$GLOBALS['wp_locale'] = self::$globals_cache[ $new_locale ]['wp_locale'];
		} else {
			/**
			 * WPLocaleProxy is a wrapper of \WP_Locale with a kind of lazy initialization
			 * to avoid loading the default domain for strings that
			 * we don't use in this transitory language.
			 */
			$GLOBALS['wp_locale'] = new WPLocaleProxy();
		}
	}

	/**
	 * @param string $new_locale
	 */
	private function changeMoObjects( $new_locale ) {
		$this->resetTranslationAvailabilityInformation();

		$cachedMoObjects = isset( self::$globals_cache[ $new_locale ]['l10n'] )
			? self::$globals_cache[ $new_locale ]['l10n']
			: [];

		/**
		 * The JustInTimeMO objects will replaced themselves on the fly
		 * by the legacy default MO object if a string is translated.
		 * This is because the function "_load_textdomain_just_in_time"
		 * does not support the default domain and MO files outside the
		 * "wp-content/languages" folder.
		 */
		$GLOBALS['l10n'] = $this->jit_mo_factory->get( $new_locale, $this->getUnloadedDomains(), $cachedMoObjects );

		$this->setLocaleInWP65TranslationController( $new_locale );
	}

	/**
	 * @param string $new_locale
	 *
	 * @return void
	 */
	private function setLocaleInWP65TranslationController( $new_locale ) {
		if ( class_exists( \WP_Translation_Controller::class ) ) {
			\WP_Translation_Controller::get_instance()->set_locale( $new_locale );
		}
	}

	private function resetTranslationAvailabilityInformation() {
		global $wp_textdomain_registry;
		if ( ! isset( $wp_textdomain_registry ) && function_exists( '_get_path_to_translation' ) ) {
			_get_path_to_translation( '', true );
		}
	}

	/**
	 * @param string $locale
	 *
	 * @return string
	 */
	public function filterLocale( $locale ) {
		$currentLocale = $this->getCurrentLocale();

		if ( $currentLocale ) {
			return $currentLocale;
		}

		return $locale;
	}

	/**
	 * @return array
	 */
	private function getUnloadedDomains() {
		$unloadedDomains = isset( $GLOBALS['l10n_unloaded'] ) ? array_keys( (array) $GLOBALS['l10n_unloaded'] ) : [];

		// WP 6.5
		// When a text domain is unloaded, and later loaded again, WP will keep it in l10n_unloaded.
		// Prior to WP 6.5, there was a call ``unset( $l10n_unloaded[ $domain ] );`` just after
		// $l10n[ $domain ] was set.
		// This is a problem because the domain will be always excluded in our JITMO objects,
		// and will generate empty domains that should be loaded.
		if ( class_exists('\WP_Translation_Controller') ) {
			foreach ( $unloadedDomains as $key => $domain ) {
				if ( isset( $GLOBALS['l10n'][ $domain ] ) && ! $GLOBALS['l10n'][ $domain ] instanceof \NOOP_Translations ) {
					unset( $unloadedDomains[ $key ] );
				}
			}
		}
		return $unloadedDomains;
	}
}
