<?php
/**
 * @author OnTheGo Systems
 */
class WPML_TM_ATE {
	const SITE_ID_SCOPE = 'ate';

	private $translation_method_ate_enabled;
	/**
	 * @var WPML_TM_ATE_API
	 */
	private $tm_ate_api;
	/**
	 * @var WPML_TM_ATE_Jobs
	 */
	private $tm_ate_jobs;

	public function is_translation_method_ate_enabled() {
		if ( null === $this->translation_method_ate_enabled ) {
			$tm_settings            = wpml_get_setting_filter( null, 'translation-management' );
			$doc_translation_method = null;
			if ( $tm_settings && array_key_exists( 'doc_translation_method', $tm_settings ) ) {
				$doc_translation_method = $tm_settings['doc_translation_method'];
			}
			$this->translation_method_ate_enabled = $doc_translation_method === ICL_TM_TMETHOD_ATE;
		}

		return $this->translation_method_ate_enabled;
	}

	/**
	 * @param int    $trid
	 * @param string $language
	 *
	 * @return bool
	 */
	public function is_translation_ready_for_post( $trid, $language ) {

		$translation_status_id = $this->get_translation_status_id_for_post( $trid, $language );

		return $translation_status_id && ! in_array( $translation_status_id, array( WPML_TM_ATE_Job::ATE_JOB_CREATED, WPML_TM_ATE_Job::ATE_JOB_IN_PROGRESS ), true );
	}

	/**
	 * @param int    $trid
	 * @param string $language
	 *
	 * @return int|bool
	 */
	public function get_translation_status_id_for_post( $trid, $language ) {

		$status_id = false;

		$ate_job = $this->get_job_data_for_post( $trid, $language );

		if ( $ate_job && ! is_wp_error( $ate_job ) ) {
			$status_id = $ate_job->status_id;
		}

		return $status_id;
	}

	/**
	 * @param int    $trid
	 * @param string $language
	 *
	 * @return array|WP_Error
	 */
	public function get_job_data_for_post( $trid, $language ) {

		$tm_ate_api  = $this->get_tm_ate_api();
		$tm_ate_jobs = $this->get_tm_ate_jobs();
		$core_tm     = wpml_load_core_tm();

		$job_id = $core_tm->get_translation_job_id( $trid, $language );
		$editor = $core_tm->get_translation_job_editor( $trid, $language );

		if ( \WPML_TM_Editors::ATE !== strtolower( $editor ) ) {
			return null;
		}

		$ate_job_id = $tm_ate_jobs->get_ate_job_id( $job_id );
		$ate_job    = $tm_ate_api->get_job( $ate_job_id );

		return isset( $ate_job->$ate_job_id ) ? $ate_job->$ate_job_id : $ate_job;
	}

	/**
	 * @return WPML_TM_ATE_API
	 */
	private function get_tm_ate_api() {
		if ( null === $this->tm_ate_api ) {
			$ams_ate_factories = wpml_tm_ams_ate_factories();
			$this->tm_ate_api  = $ams_ate_factories->get_ate_api();
		}

		return $this->tm_ate_api;
	}

	/**
	 * @return WPML_TM_ATE_Jobs
	 */
	private function get_tm_ate_jobs() {

		if ( null === $this->tm_ate_jobs ) {
			$ate_jobs_records  = wpml_tm_get_ate_job_records();
			$this->tm_ate_jobs = new WPML_TM_ATE_Jobs( $ate_jobs_records );
		}

		return $this->tm_ate_jobs;
	}

}
