<?php

namespace WPML\ST\MO\Hooks;

use WPML\ST\MO\File\Manager;
use WPML\ST\MO\LoadedMODictionary;
use WPML_ST_Translations_File_Locale;
use function WPML\FP\partial;
use WPML\LIB\WP\WordPress;
use WPML\ST\MO\Hooks\LoadTranslationFile;

class LoadTextDomain implements \IWPML_Action {

	const PRIORITY_OVERRIDE = 10;

	/** @var Manager $file_manager */
	private $file_manager;

	/** @var WPML_ST_Translations_File_Locale $file_locale */
	private $file_locale;

	/** @var LoadedMODictionary $loaded_mo_dictionary */
	private $loaded_mo_dictionary;

	/** @var array $loaded_domains */
	private $loaded_domains = [];

	public function __construct(
		Manager $file_manager,
		WPML_ST_Translations_File_Locale $file_locale,
		LoadedMODictionary $loaded_mo_dictionary
	) {
		$this->file_manager         = $file_manager;
		$this->file_locale          = $file_locale;
		$this->loaded_mo_dictionary = $loaded_mo_dictionary;
	}

	public function add_hooks() {
		$this->reloadAlreadyLoadedMOFiles();

		add_filter( 'override_load_textdomain', [ $this, 'overrideLoadTextDomain' ], 10, 3 );
		add_filter( 'override_unload_textdomain', [ $this, 'overrideUnloadTextDomain' ], 10, 2 );
		add_action( 'wpml_language_has_switched', [ $this, 'languageHasSwitched' ] );
	}

	/**
	 * When a MO file is loaded, we override the process to load
	 * the custom MO file before.
	 *
	 * That way, the custom MO file will be merged into the subsequent
	 * native MO files and the custom MO translations will always
	 * overwrite the native ones.
	 *
	 * This gives us the ability to build partial custom MO files
	 * with only the modified translations.
	 *
	 * @param bool   $override Whether to override the .mo file loading. Default false.
	 * @param string $domain   Text domain. Unique identifier for retrieving translated strings.
	 * @param string $mofile   Path to the MO file.
	 *
	 * @return bool
	 */
	public function overrideLoadTextDomain( $override, $domain, $mofile ) {
		if ( ! $mofile ) {
			return $override;
		}


		if ( ! $this->isCustomMOLoaded( $domain ) ) {
			remove_filter( 'override_load_textdomain', [ $this, 'overrideLoadTextDomain' ], 10 );
			$locale = $this->file_locale->get( $mofile, $domain );
			$this->fallbackDefaultTranslations( $mofile, $domain, $locale );
			$this->loadCustomMOFile( $domain, $mofile, $locale );
			add_filter( 'override_load_textdomain', [ $this, 'overrideLoadTextDomain' ], 10, 3 );
		}

		$this->loaded_mo_dictionary->addFile( $domain, $mofile );

		return $override;
	}

	/**
	 * @param bool $override
	 * @param string $domain
	 *
	 * @return bool
	 */
	public function overrideUnloadTextDomain( $override, $domain ) {
		$key = array_search( $domain, $this->loaded_domains );

		if ( false !== $key ) {
			unset( $this->loaded_domains[ $key ] );
		}

		return $override;
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function isCustomMOLoaded( $domain ) {
		return in_array( $domain, $this->loaded_domains, true );
	}

	private function loadCustomMOFile( $domain, $mofile, $locale ) {
		$wpml_mofile = $this->file_manager->get( $domain, $locale );

		if ( $wpml_mofile && $wpml_mofile !== $mofile ) {
			$defaultTextdomainPath = LoadTranslationFile::getDefaultWordPressTranslationPath( $domain, $locale );

			load_textdomain( $domain, $wpml_mofile );

			if ( $defaultTextdomainPath ) {
				$this->maybeLoadWordPressJITMoFile( $defaultTextdomainPath, $domain );
			}
		}

		$this->setCustomMOLoaded( $domain );
	}

	/**
	 * @param string|null $path
	 * @param string $domain
	 * @return void
	 */
	private function maybeLoadWordPressJITMoFile( $path, $domain ) {
		if( file_exists( $path ) ) {
			load_textdomain( $domain, $path );
		}
	}

	private function reloadAlreadyLoadedMOFiles() {
		$this->loaded_mo_dictionary->getEntities()->each( function ( $entity ) {
			unload_textdomain( $entity->domain );
			$locale = $this->file_locale->get( $entity->mofile, $entity->domain );
			$this->loadCustomMOFile( $entity->domain, $entity->mofile, $locale );
			if ( class_exists( '\WP_Translation_Controller' ) ) {
				// WP 6.5 - passing locale
				load_textdomain( $entity->domain, $entity->mofile, $locale );
			} else {
				load_textdomain($entity->domain, $entity->mofile);
			}
		} );
	}

	/**
	 * @param string $domain
	 */
	private function setCustomMOLoaded( $domain ) {
		$this->loaded_domains[] = $domain;
	}

	public function languageHasSwitched() {
		$this->loaded_domains = [];
	}

	/**
	 * @param $mofile
	 * @param $domain
	 */
	public function fallbackDefaultTranslations( $mofile, $domain, $locale) {
		// Since version 6.7, WP will not attempt anymore to load translations
		// from the default WordPress translation path if the MO file is not found.
		// It will set $GLOBALS['l10n'][ $domain ] to a NOOP_Translations object and
		// WP JIT mechanism won't be triggered anymore.
		// Thus, WPML is not able to load custom translations anymore.
		// If any of these translation sources is available, we will force it to load before this happens.
		if (WordPress::versionCompare('>', '6.6.999') && is_string( $mofile )) {
			$wpml_mofile = $this->file_manager->get($domain, $locale);

			$replaced_mofile = LoadTranslationFile::replaceMoExtensionWithPhp( $mofile );
			if (!file_exists($mofile) && !file_exists($replaced_mofile) && $wpml_mofile) {
				$defaultTranslationsFile = LoadTranslationFile::getDefaultWordPressTranslationPath($domain, $locale) ?: $wpml_mofile;
				LoadTranslationFile::replaceTranslationFile($domain, $mofile, $defaultTranslationsFile);
			}
		}
	}
}
