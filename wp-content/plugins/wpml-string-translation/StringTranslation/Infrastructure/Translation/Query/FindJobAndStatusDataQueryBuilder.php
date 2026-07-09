<?php

namespace WPML\StringTranslation\Infrastructure\Translation\Query;

class FindJobAndStatusDataQueryBuilder {

	protected function getPrefix(): string {
		global $wpdb;
		return $wpdb->prefix;
	}

	/**
	 * @param int[] $rids
	 *
	 * @return string
	 */
	public function build( array $rids ) {
		$sql = "
            SELECT
                job.rid,
                job.job_id,
                job.translator_id,
                job.automatic,
                job.editor,
                translation_status.translation_service,
                translation_status.review_status
            FROM {$this->getPrefix()}icl_translate_job job
            LEFT JOIN {$this->getPrefix()}icl_translation_status translation_status
                ON job.rid = translation_status.rid
            INNER JOIN (
                SELECT rid, MAX(job_id) AS max_job_id
                FROM {$this->getPrefix()}icl_translate_job
                WHERE rid IN (" . wpml_prepare_in( $rids, '%d' ) . ')
                GROUP BY rid
            ) max_jobs ON job.rid = max_jobs.rid AND job.job_id = max_jobs.max_job_id';

		return $sql;
	}
}
