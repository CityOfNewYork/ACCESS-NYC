<?php

class WPML_TP_Apply_Single_Job {
	/** @var WPML_TP_Translations_Repository */
	private $translations_repository;

	/** @var WPML_TP_Apply_Translation_Strategies */
	private $strategy_dispatcher;

	/**
	 * @param WPML_TP_Translations_Repository      $translations_repository
	 * @param WPML_TP_Apply_Translation_Strategies $strategy_dispatcher
	 */
	public function __construct(
		WPML_TP_Translations_Repository $translations_repository,
		WPML_TP_Apply_Translation_Strategies $strategy_dispatcher
	) {
		$this->translations_repository = $translations_repository;
		$this->strategy_dispatcher     = $strategy_dispatcher;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 * @throws WPML_TP_API_Exception
	 */
	public function apply( WPML_TM_Job_Entity $job ) {
		$translations = $this->translations_repository->get_job_translations_by_job_entity( $job );

		$this->strategy_dispatcher->get( $job )->apply( $job, $translations );
		$job->set_status( ICL_TM_COMPLETE );

		return $job;
	}
}