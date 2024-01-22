<?php

namespace WPML\TM\Upgrade\Commands;

class ResetTranslatorOfAutomaticJobs implements \IWPML_Upgrade_Command {
	/** @var bool $result */
	private $result = false;

	public function run_admin() {
		global $wpdb;

		$automatic_column_exists = $wpdb->get_var(
			"SHOW COLUMNS
			FROM `{$wpdb->prefix}icl_translate_job`
			LIKE 'automatic'"
		);

		if ( ! $automatic_column_exists ) {
			// No need to reset translator of automatic jobs
			// as this site never used automatic translation.
			// Return true to mark this upgrade as done.
			return true;
		}

		$subquery = "
		SELECT job_id, rid
		FROM {$wpdb->prefix}icl_translate_job
		WHERE job_id IN (
			SELECT MAX(job_id)
	        FROM {$wpdb->prefix}icl_translate_job					
	        GROUP BY rid
		) AND automatic = 1
		";

		$rowsToUpdate = $wpdb->get_results( $subquery );

		if ( count( $rowsToUpdate ) ) {
			$rids = \wpml_prepare_in( array_column( $rowsToUpdate, 'rid' ), '%d' );
			$sql  = "
				UPDATE {$wpdb->prefix}icl_translation_status translation_status
				SET translation_status.translator_id = 0
				WHERE translation_status.rid IN ( $rids )
			";
			$wpdb->query( $sql );

			$jobIds = \wpml_prepare_in( array_column( $rowsToUpdate, 'job_id' ), '%d' );
			$sql    = "
				UPDATE {$wpdb->prefix}icl_translate_job
				SET translator_id = 0
				WHERE job_id IN ( $jobIds ) 			       		
			";
			$wpdb->query( $sql );
		}

		$this->result = true;

		return $this->result;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_ajax() {
		return null;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_frontend() {
		return null;
	}

	/**
	 * @return bool
	 */
	public function get_results() {
		return $this->result;
	}

}
