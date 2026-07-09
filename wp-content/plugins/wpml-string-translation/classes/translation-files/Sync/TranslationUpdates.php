<?php

namespace WPML\ST\TranslationFile\Sync;

use WPML\Collect\Support\Collection;

class TranslationUpdates {

	// The global constant is not defined yet.
	const ICL_STRING_TRANSLATION_COMPLETE = 10;

	/** @var \wpdb */
	private $wpdb;

	/** @var \WPML_Language_Records */
	private $languageRecords;

	/** @var null|Collection */
	private $data;

	public function __construct( \wpdb $wpdb, \WPML_Language_Records $languageRecords ) {
		$this->wpdb            = $wpdb;
		$this->languageRecords = $languageRecords;
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 *
	 * @return int
	 */
	public function getTimestamp( $domain, $locale ) {
		$this->loadData();
		$lang = $this->languageRecords->get_language_code( $locale );
		return (int) $this->data->get( "$lang#$domain" );
	}

	private function loadData() {
		if ( ! $this->data ) {
			$sql = "
				SELECT
					CONCAT(st.language,'#',s.context) AS lang_domain,
					UNIX_TIMESTAMP(MAX(st.translation_date)) as last_update
				FROM {$this->wpdb->prefix}icl_string_translations AS st
				INNER JOIN {$this->wpdb->prefix}icl_strings AS s
					ON st.string_id = s.id
				WHERE st.value IS NOT NULL AND st.status = %d
				GROUP BY s.context, st.language;
			";

			$this->data = wpml_collect(
				$this->wpdb->get_results( $this->wpdb->prepare( $sql, self::ICL_STRING_TRANSLATION_COMPLETE ) )
			)->pluck( 'last_update', 'lang_domain' );
		}
	}

	public function reset() {
		$this->data = null;
	}
}
