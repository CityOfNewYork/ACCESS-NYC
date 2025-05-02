<?php

namespace WPML\ST\TranslationFile;

use wpdb;
use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Just;
use WPML\FP\Maybe;
use WPML\FP\Nothing;
use WPML\LIB\WP\Cache;
use WPML\ST\Package\Domains as PackageDomains;
use WPML_Admin_Texts;
use WPML_ST_Blog_Name_And_Description_Hooks;
use WPML_ST_Translations_File_Dictionary;
use WPML\ST\Shortcode;

class Domains {

	const MO_DOMAINS_CACHE_GROUP = 'WPML_ST_CACHE';
	const MO_DOMAINS_CACHE_KEY   = 'wpml_string_translation_has_mo_domains';

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var PackageDomains $package_domains */
	private $package_domains;

	/** @var WPML_ST_Translations_File_Dictionary $file_dictionary */
	private $file_dictionary;

	/** @var null|Collection $jed_domains */
	private static $jed_domains;

	/**
	 * Domains constructor.
	 *
	 * @param wpdb                                 $wpdb            The WordPress database instance.
	 * @param PackageDomains                       $package_domains The package domains instance.
	 * @param WPML_ST_Translations_File_Dictionary $file_dictionary The translations file dictionary.
	 */
	public function __construct(
		wpdb $wpdb,
		PackageDomains $package_domains,
		WPML_ST_Translations_File_Dictionary $file_dictionary
	) {
		$this->wpdb            = $wpdb;
		$this->package_domains = $package_domains;
		$this->file_dictionary = $file_dictionary;
	}


	/**
	 * @return Collection
	 */
	public function getMODomains() {
		$getMODomainsFromDB = function () {
			$cacheLifeTime = HOUR_IN_SECONDS;

			$excluded_domains = self::getReservedDomains()->merge( $this->getJEDDomains() );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = "
				SELECT DISTINCT context {$this->getCollateForContextColumn()}
				FROM {$this->wpdb->prefix}icl_strings
			";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$mo_domains = wpml_collect( $this->wpdb->get_col( $sql ) )
				->diff( $excluded_domains )
				->values();

			if ( $mo_domains->count() <= 0 ) {
				// if we don't get any data from DB, we set cache expire time to be 15 minutes so that cache refreshes in lesser time.
				$cacheLifeTime = 15 * MINUTE_IN_SECONDS;
			}

			Cache::set(
				self::MO_DOMAINS_CACHE_GROUP,
				self::MO_DOMAINS_CACHE_KEY,
				$cacheLifeTime,
				$mo_domains
			);

			return $mo_domains;
		};

		/** @var Just|Nothing $cacheItem */
		$cacheItem = Cache::get( self::MO_DOMAINS_CACHE_GROUP, self::MO_DOMAINS_CACHE_KEY );
		return $cacheItem->getOrElse( $getMODomainsFromDB );
	}

	public static function invalidateMODomainCache() {
		static $invalidationScheduled = false;

		if ( ! $invalidationScheduled ) {
			$invalidationScheduled = true;
			add_action(
				'shutdown',
				function () {
					Cache::flushGroup( self::MO_DOMAINS_CACHE_GROUP );
				}
			);
		}
	}

	/**
	 * @return string
	 */
	private function getCollateForContextColumn() {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared		
		$sql = "
			SELECT COLLATION_NAME
			 FROM information_schema.columns
			 WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '{$this->wpdb->prefix}icl_strings' AND COLUMN_NAME = 'context'
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$collation = $this->wpdb->get_var( $sql );
		if ( ! $collation ) {
			return '';
		}

		list( $type ) = explode( '_', $collation );

		// Whitelisted character sets that are known to support _bin collation.
		$supported_charsets = [ 'utf8', 'utf8mb3', 'utf8mb4', 'latin1', 'latin2', 'ascii', 'binary' ];

		if ( in_array( $type, $supported_charsets ) ) {
			if ( 'binary' === $type ) {
				return 'COLLATE binary';
			} else {
				return 'COLLATE ' . $type . '_bin';
			}
		}

		// Return empty string for unsupported character sets to avoid MySQL errors.
		return '';
	}

	/**
	 * Returns a collection of MO domains that
	 * WPML needs to automatically load.
	 *
	 * @return Collection
	 */
	public function getCustomMODomains() {
		$all_mo_domains    = $this->getMODomains();
		$native_mo_domains = $this->file_dictionary->get_domains( 'mo', get_locale() );

		return $all_mo_domains->reject(
			function ( $domain ) use ( $native_mo_domains ) {
				/**
				 * Admin texts, packages, string shortcodes are handled separately,
				 * so they are loaded on-demand.
				 */
				return null === $domain
					   || 0 === strpos( $domain, WPML_Admin_Texts::DOMAIN_NAME_PREFIX )
					   || $this->package_domains->isPackage( $domain )
					   || Shortcode::STRING_DOMAIN === $domain
					   || in_array( $domain, $native_mo_domains, true );
			}
		)->values();
	}

	/**
	 * @return Collection
	 */
	public function getJEDDomains() {
		if ( ! self::$jed_domains instanceof Collection ) {
			self::$jed_domains = wpml_collect( $this->file_dictionary->get_domains( 'json' ) );
		}

		return self::$jed_domains;
	}

	public static function resetCache() {
		self::invalidateMODomainCache();
		self::$jed_domains = null;
	}

	/**
	 * Domains that are not handled with MO files,
	 * but have direct DB queries.
	 *
	 * @return Collection
	 */
	public static function getReservedDomains() {
		return wpml_collect(
			[
				WPML_ST_Blog_Name_And_Description_Hooks::STRING_DOMAIN,
			]
		);
	}

	/**
	 * @return Collection
	 */
	private function getPackageDomains() {
		return $this->package_domains->getDomains();
	}
}
