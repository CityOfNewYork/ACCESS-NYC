<?php

namespace WPML\ST\Troubleshooting\Cleanup;

use wpdb;
use WPML_ST_Translations_File_Dictionary;

class Database {

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var WPML_ST_Translations_File_Dictionary $dictionary */
	private $dictionary;

	public function __construct(
		wpdb $wpdb,
		WPML_ST_Translations_File_Dictionary $dictionary
	) {
		$this->wpdb       = $wpdb;
		$this->dictionary = $dictionary;
	}

	public function deleteStringsFromImportedMoFiles() {
		$moDomains = $this->dictionary->get_domains( 'mo' );

		if ( ! $moDomains ) {
			return;
		}

		$this->deleteOnlyNativeMoStringTranslations( $moDomains );
		$this->deleteMoStringsWithNoTranslation( $moDomains );
		icl_update_string_status_all();
		$this->optimizeStringTables();
	}

	private function deleteOnlyNativeMoStringTranslations( array $moDomains ) {
		$this->wpdb->query(
			"
			DELETE st FROM {$this->wpdb->prefix}icl_string_translations AS st
			LEFT JOIN {$this->wpdb->prefix}icl_strings AS s
				ON st.string_id = s.id
			WHERE st.value IS NULL AND s.context IN(" . wpml_prepare_in( $moDomains ) . ')
		'
		);
	}

	private function deleteMoStringsWithNoTranslation( array $moDomains ) {
		$this->wpdb->query(
			"
			DELETE s FROM {$this->wpdb->prefix}icl_strings AS s
			LEFT JOIN {$this->wpdb->prefix}icl_string_translations AS st
				ON st.string_id = s.id
			WHERE st.string_id IS NULL AND s.context IN(" . wpml_prepare_in( $moDomains ) . ')
		'
		);
	}

	private function optimizeStringTables() {
			$this->wpdb->query( "OPTIMIZE TABLE {$this->wpdb->prefix}icl_strings, {$this->wpdb->prefix}icl_string_translations" );
	}

	public function truncatePagesAndUrls() {
		foreach ( [ 'icl_string_pages', 'icl_string_urls' ] as $table ) {
			$table = $this->wpdb->prefix . $table;

			if ( $this->tableExists( $table ) ) {
				$this->wpdb->query( "TRUNCATE $table" );
			}
		}
	}

	/**
	 * @param string $table
	 *
	 * @return bool
	 */
	private function tableExists( $table ) {
		/** @var string $sql */
		$sql = $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table );
		return (bool) $this->wpdb->get_var( $sql );
	}
}
