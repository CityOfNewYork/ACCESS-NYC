<?php

class WPML_TM_ATE_Jobs_Sync_Script_Loader {
	/** @var WPML_TM_ATE_Job_Repository */
	private $ate_jobs_repository;

	/** @var WPML_TM_Scripts_Factory */
	private $script_factory;

	/**
	 * @param WPML_TM_ATE_Job_Repository $ate_jobs_repository
	 * @param WPML_TM_Scripts_Factory    $script_factory
	 */
	public function __construct(
		WPML_TM_ATE_Job_Repository $ate_jobs_repository,
		WPML_TM_Scripts_Factory $script_factory
	) {
		$this->ate_jobs_repository = $ate_jobs_repository;
		$this->script_factory      = $script_factory;
	}


	public function load() {
		$translation_jobs = $this->ate_jobs_repository->get_jobs_to_sync();

		if ( ! $translation_jobs->count() ) {
			return;
		}

		$js_handler = 'wpml-tm-ate-jobs-sync';

		wp_register_script(
			$js_handler,
			WPML_TM_URL . '/dist/js/ate/jobs-sync-app.js',
			array(),
			WPML_TM_VERSION
		);
		wp_localize_script( $js_handler, 'jobIds', $translation_jobs->map_to_property( 'translate_job_id' ) );

		wp_enqueue_script( $js_handler );

		$this->script_factory->localize_script( 'wpml-tm-ate-jobs-sync' );
	}
}