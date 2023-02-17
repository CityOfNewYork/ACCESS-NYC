<?php

namespace WPML\ST\StringsCleanup;

class UntranslatedStrings {

	/** @var \wpdb */
	private $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param string[] $domains
	 *
	 * @return int
	 */
	public function getCountInDomains( $domains ) {
		if ( ! $domains ) {
			return 0;
		}

		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT count(id) FROM {$this->wpdb->prefix}icl_strings WHERE status = %d AND context IN ("
				. wpml_prepare_in( $domains, '%s' ) . ')',
				ICL_TM_NOT_TRANSLATED
			)
		);
	}

	/**
	 * @param string[] $domains
	 * @param int      $batchSize
	 *
	 * @return array
	 */
	public function getFromDomains( $domains, $batchSize ) {
		if ( ! $domains ) {
			return [];
		}

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT id FROM {$this->wpdb->prefix}icl_strings WHERE status = %d AND context IN ("
				. wpml_prepare_in( $domains, '%s' ) . ') LIMIT 0, %d',
				ICL_TM_NOT_TRANSLATED,
				$batchSize
			)
		);
	}

	/**
	 * @param int[] $stringIds
	 *
	 * @return int
	 */
	public function remove( $stringIds ) {
		if ( $stringIds ) {
			wpml_unregister_string_multi( $stringIds );
		}

		return count( $stringIds );
	}
}
