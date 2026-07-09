<?php

namespace WPML\StringTranslation\Infrastructure\StringPackage\Query;

class JobsQuery {

	/** @var \wpdb */
	private $wpdb;

	/** @var \SitePress */
	private $sitepress;

	public function __construct(
		$wpdb,
		$sitepress
	) {
		$this->wpdb = $wpdb;
		$this->sitepress = $sitepress;
	}

	public function get( array $packages ) {
		$rids = [];

		foreach ( $packages as $package ) {
			$translationStatuses = $package['translation_statuses'];
			$languageStatuses    = explode( ';', $translationStatuses );
			foreach ( $languageStatuses as $languageStatus ) {
				$rid = $this->getRidFromTranslationStatus( $languageStatus );
				if ( $rid ) {
					$rids[] = $rid;
				}
			}
		}

		$rids = array_unique( $rids );
		if ( empty( $rids ) ) {
			return null;
		}
		$sql = "
            SELECT
                tj.rid,
                tj.job_id,
                tj.translator_id,
                ts.status,
                ts.review_status,
                ts.needs_update,
                tj.automatic,
                ts.translation_service,
                tj.editor,
                tj.translated
            FROM {$this->wpdb->prefix}icl_translate_job tj
            LEFT JOIN {$this->wpdb->prefix}icl_translation_status ts
                ON tj.rid = ts.rid
            INNER JOIN (
            SELECT
              rid,
              MAX(job_id) AS max_job_id
            FROM {$this->wpdb->prefix}icl_translate_job
            WHERE rid IN (" . wpml_prepare_in( $rids, '%d' ) . ")
            GROUP BY rid
            ) latest_jobs
            ON tj.rid = latest_jobs.rid
            AND tj.job_id = latest_jobs.max_job_id
       ";

		return $this->wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * @param string $translationStatus
	 * @return int|null
	 */
	private function getRidFromTranslationStatus( string $translationStatus ) {
		$matches = [];
		preg_match( '/rid:(\d+)/', $translationStatus, $matches );

		return count( $matches ) > 0 ? intval( $matches[1] ) : null;
	}
}