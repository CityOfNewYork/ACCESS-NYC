<?php

namespace WPML\ST\MO\Generate;

use wpdb;
use WPML\Collect\Support\Collection;
use function WPML\Container\make;
use WPML\ST\TranslationFile\Domains;
use function wpml_collect;
use WPML_Locale;

class DomainsAndLanguagesRepository {
	/** @var wpdb */
	private $wpdb;

	/** @var Domains */
	private $domains;

	/** @var WPML_Locale */
	private $locale;

	/**
	 * @param wpdb        $wpdb
	 * @param Domains     $domains
	 * @param WPML_Locale $wp_locale
	 */
	public function __construct( wpdb $wpdb, Domains $domains, WPML_Locale $wp_locale ) {
		$this->wpdb    = $wpdb;
		$this->domains = $domains;
		$this->locale  = $wp_locale;
	}


	/**
	 * @return Collection
	 */
	public function get() {
		return $this->getAllDomains()->map( function ( $row ) {
			return (object) [
				'domain' => $row->domain,
				'locale' => $this->locale->get_locale( $row->languageCode )
			];
		} )->values();
	}

	/**
	 * @return Collection
	 */
	private function getAllDomains() {
		$moDomains = $this->domains->getMODomains()->toArray();
		if ( ! $moDomains ) {
			return wpml_collect( [] );
		}

		$sql    = "
			SELECT DISTINCT (BINARY s.context) as `domain`, st.language as `languageCode`
			FROM {$this->wpdb->prefix}icl_string_translations st
			INNER JOIN {$this->wpdb->prefix}icl_strings s ON s.id = st.string_id
			WHERE st.`status` = 10 AND ( st.`value` != st.mo_string OR st.mo_string IS NULL)
				AND s.context IN(" . wpml_prepare_in( $moDomains ) . ")
		";
		$result = $this->wpdb->get_results( $sql );

		return wpml_collect( $result );
	}

	/**
	 * @return bool
	 */
	public static function hasTranslationFilesTable() {
		return make( \WPML_Upgrade_Schema::class )->does_table_exist( 'icl_mo_files_domains' );
	}
}