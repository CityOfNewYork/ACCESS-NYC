<?php

namespace WPML\ST\TranslationFile;

use wpdb;
use WPML\Collect\Support\Collection;
use WPML\ST\Package\Domains as PackageDomains;
use WPML_Admin_Texts;
use function wpml_prepare_in;
use WPML_Slug_Translation;
use WPML_ST_Blog_Name_And_Description_Hooks;
use WPML_ST_Translations_File_Dictionary;
use WPML\ST\Shortcode;

class Domains {

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var PackageDomains $package_domains */
	private $package_domains;

	/** @var WPML_ST_Translations_File_Dictionary $file_dictionary */
	private $file_dictionary;

	/** @var null|Collection $mo_domains */
	private static $mo_domains;

	/** @var null|Collection $jed_domains */
	private static $jed_domains;

	/**
	 * Domains constructor.
	 *
	 * @param PackageDomains                       $package_domains
	 * @param WPML_ST_Translations_File_Dictionary $file_dictionary
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
		if ( ! self::$mo_domains instanceof Collection ) {
			$excluded_domains = self::getReservedDomains()
									 ->merge( $this->getJEDDomains() );

			$sql = "
				SELECT DISTINCT context {$this->getCollateForContextColumn()}
				FROM {$this->wpdb->prefix}icl_strings
			";

			self::$mo_domains = wpml_collect( $this->wpdb->get_col( $sql ) )
				->diff( $excluded_domains )
				->values();

		}

		return self::$mo_domains;
	}

	/**
	 * @return string
	 */
	private function getCollateForContextColumn() {
		$sql = "
			SELECT COLLATION_NAME
			 FROM information_schema.columns
			 WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '{$this->wpdb->prefix}icl_strings' AND COLUMN_NAME = 'context'
		";

		$collation = $this->wpdb->get_var( $sql );
		if ( ! $collation ) {
			return '';
		}

		list( $type ) = explode( '_', $collation );
		if ( in_array( $type, [ 'utf8', 'utf8mb4' ] ) ) {
			return 'COLLATE ' . $type . '_bin';
		}

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
			function( $domain ) use ( $native_mo_domains ) {
				/**
				 * Admin texts, packages, string shortcodes are handled separately,
				 * so they are loaded on-demand.
				 */
				return 0 === strpos( $domain, WPML_Admin_Texts::DOMAIN_NAME_PREFIX )
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
		self::$mo_domains  = null;
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
