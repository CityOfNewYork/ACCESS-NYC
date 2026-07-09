<?php

namespace WPML\ST\TranslationFile;

trait StringCollation {

	/**
	 * @param \wpdb $wpdb
	 * @return string
	 */
	private function getCollateForContextColumn( \wpdb $wpdb ) {
		static $collation_suffix = null;

		if ( null !== $collation_suffix ) {
			return $collation_suffix;
		}

		$collation_suffix = \get_transient( 'wpml_st_context_collation' );

		if ( false !== $collation_suffix ) {
			return $collation_suffix;
		}

		$collation_suffix = '';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql = "
			SELECT COLLATION_NAME
			 FROM information_schema.columns
			 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$wpdb->prefix}icl_strings' AND COLUMN_NAME = 'context'
		";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$collation = $wpdb->get_var( $sql );
		if ( $collation ) {
			list( $type ) = explode( '_', $collation );

			// Whitelisted character sets that are known to support _bin collation.
			$supported_charsets = [ 'utf8', 'utf8mb3', 'utf8mb4', 'latin1', 'latin2', 'ascii', 'binary' ];

			if ( in_array( $type, $supported_charsets ) ) {
				if ( 'binary' === $type ) {
					$collation_suffix = 'COLLATE binary';
				} else {
					$collation_suffix = 'COLLATE ' . $type . '_bin';
				}
			}
		}

		\set_transient( 'wpml_st_context_collation', $collation_suffix );

		return $collation_suffix;
	}
}
