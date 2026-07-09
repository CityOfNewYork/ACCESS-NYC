<?php

namespace WPML\ST\MO\Hooks;

use WPML\FP\Lst;
use WPML\ST\MO\File\Manager;
use WPML\ST\MO\JustInTime\MO;
use WPML\ST\MO\LoadedMODictionary;
use WPML\ST\Storage\StoragePerLanguageInterface;
use WPML\ST\TranslationFile\Domains;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;
use WPML_Locale;

class CustomTextDomains implements \IWPML_Action {
	const CACHE_ID  = 'wpml-st-custom-mo-files';
	const CACHE_ALL_LOCALES = 'locales';
	const CACHE_KEY_DOMAINS = 'domains';
	const CACHE_KEY_FILES = 'files';

	/** @var Manager $manager */
	private $manager;

	/** @var Domains $domains */
	private $domains;

	/** @var LoadedMODictionary $loadedDictionary */
	private $loadedDictionary;

	/** @var StoragePerLanguageInterface */
	private $cache;

	/** @var WPML_Locale */
	private $locale;

	/** @var callable */
	private $syncMissingFile;

	/** @var string[] $loaded_custom_domains */
	private $loaded_custom_domains = [];

	public function __construct(
		Manager $file_manager,
		Domains $domains,
		LoadedMODictionary $loadedDictionary,
		StoragePerLanguageInterface $cache,
		WPML_Locale $locale,
		callable $syncMissingFile = null
	) {
		$this->manager          = $file_manager;
		$this->domains          = $domains;
		$this->loadedDictionary = $loadedDictionary;
		$this->cache            = $cache;
		$this->locale           = $locale;
		$this->syncMissingFile  = $syncMissingFile ?: function () {};

		// Flush cache when a custom MO file is written, removed or updated.
		add_action( 'wpml_st_translation_file_written', [ $this, 'clear_cache' ], 10, 0 );
		add_action( 'wpml_st_translation_file_removed', [ $this, 'clear_cache' ], 10, 0 );
		// The filename could be changed on update, that's why we need to clear the cache.
		add_action( 'wpml_st_translation_file_updated', [ $this, 'clear_cache' ], 10, 0 );
	}

	public function clear_cache() {
		$locales = $this->cache->get( self::CACHE_ALL_LOCALES );
		if ( ! is_array( $locales ) ) {
			// No cache.
			return;
		}

		// Clear cache for all locales, because the domains list will change
		// for all languages when a the first custom translation file is written
		// for a new domain (even if the other locales don't get that file).
		foreach ( $locales as $locale ) {
			$this->cache->delete( $locale );
		}

		// Also flush the list of cached locales.
		$this->cache->delete( self::CACHE_ALL_LOCALES );
	}

	public function add_hooks() {
		$this->init_custom_text_domains();
	}


	public function init_custom_text_domains( $locale = null ) {
		$locale = $locale ?: determine_locale();

		$addJitMoToL10nGlobal = pipe( Lst::nth( 0 ), function ( $domain ) use ( $locale ) {
			// Following unset is important because WordPress is setting their
			// static $noop_translation variable by reference. Without unset it
			// would become our JustInTime/MO and is used for other domains.
			// @see wpmldev-2508
			unset( $GLOBALS['l10n'][ $domain ] );

			$this->loaded_custom_domains[] = $domain;
			$GLOBALS['l10n'][ $domain ] = new MO( $this->loadedDictionary, $locale, $domain );
		} );

		$getDomainPathTuple = function ( $domain ) use ( $locale ) {
			return [ $domain, $this->manager->getFilepath( $domain, $locale ) ];
		};

		$cache = $this->cache->get( $locale );

		// Get domains.
		if ( isset( $cache[ self::CACHE_KEY_DOMAINS ] ) ) {
			// Cache hit.
			$domains = \wpml_collect( $cache[ self::CACHE_KEY_DOMAINS ] );
		} else {
			// No cache for site domains.
			$cache_update_required = true;

			$domains = \wpml_collect( $this->domains->getCustomMODomains() );
		}

		$files = $domains->map( $getDomainPathTuple )
			->each( spreadArgs( $this->syncMissingFile ) )
			->each( spreadArgs( [ $this->loadedDictionary, 'addFile' ] ) );

		// Load local files.
		if ( isset( $cache[ self::CACHE_KEY_FILES ] ) ) {
			// Cache hit.
			$localeFiles = \wpml_collect( $cache[ self::CACHE_KEY_FILES ] );
		} else {
			// No cache for this locale readable custom .mo files.
			$cache_update_required = true;

			$isReadableFile = function ( $domainAndFilePath ) {
				return is_readable( $domainAndFilePath[1] );
			};
			$localeFiles = $files->filter( $isReadableFile );
		}

		if ( isset( $cache_update_required ) ) {
			$this->cache->save(
				$locale,
				[
					self::CACHE_KEY_DOMAINS => $domains->toArray(),
					self::CACHE_KEY_FILES   => $localeFiles->toArray(),
				]
			);

			$cache_locales = $this->cache->get( self::CACHE_ALL_LOCALES );
			$cache_locales = is_array( $cache_locales ) ? $cache_locales : [];
			if ( ! in_array( $locale, $cache_locales, true ) ) {
				$cache_locales[] = $locale;

				$this->cache->save(
					self::CACHE_ALL_LOCALES,
					array_unique( $cache_locales )
				);
			}
		}

		$localeFiles->each( $addJitMoToL10nGlobal );
	}
}
