<?php

class WPML_TP_Translations_Repository {

	/** @var WPML_TP_XLIFF_API */
	private $xliff_api;

	/** @var WPML_TM_Jobs_Repository */
	private $jobs_repository;

	/**
	 * @param WPML_TP_XLIFF_API       $xliff_api
	 * @param WPML_TM_Jobs_Repository $jobs_repository
	 */
	public function __construct( WPML_TP_XLIFF_API $xliff_api, WPML_TM_Jobs_Repository $jobs_repository ) {
		$this->xliff_api       = $xliff_api;
		$this->jobs_repository = $jobs_repository;
	}

	/**
	 * @param int  $job_id
	 * @param int  $job_type
	 * @param bool $parse When true, it returns the parsed translation, otherwise, it returns the raw XLIFF.
	 *
	 * @return WPML_TP_Translation_Collection|string
	 * @throws WPML_TP_API_Exception|InvalidArgumentException
	 */
	public function get_job_translations( $job_id, $job_type, $parse = true ) {
		$job = $this->jobs_repository->get_job( $job_id, $job_type );

		if ( ! $job ) {
			throw new InvalidArgumentException( 'Cannot find job' );
		}

		return $this->get_job_translations_by_job_entity( $job, $parse );
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 * @param bool               $parse When true, it returns the parsed translation, otherwise, it returns the raw XLIFF.
	 *
	 * @return WPML_TP_Translation_Collection|string
	 * @throws WPML_TP_API_Exception
	 */
	public function get_job_translations_by_job_entity( WPML_TM_Job_Entity $job, $parse = true ) {
		$correct_states = array( ICL_TM_TRANSLATION_READY_TO_DOWNLOAD, ICL_TM_COMPLETE );
		if ( ! in_array( $job->get_status(), $correct_states, true ) ) {
			throw new InvalidArgumentException( 'Job\'s translation is not ready.' );
		}

		if ( ! $job->get_tp_id() ) {
			throw new InvalidArgumentException( 'This is only a local job.' );
		}

		$translations = $this->xliff_api->get_remote_translations( $job->get_tp_id(), $parse );

		if ( $parse ) {
			/**
			 * It filters translations coming from the Translation Proxy.
			 *
			 * @param  \WPML_TP_Translation_Collection  $translations
			 * @param  \WPML_TM_Job_Entity  $job
			 */
			$translations = apply_filters( 'wpml_tm_proxy_translations', $translations, $job );
		}

		return $translations;
	}
}