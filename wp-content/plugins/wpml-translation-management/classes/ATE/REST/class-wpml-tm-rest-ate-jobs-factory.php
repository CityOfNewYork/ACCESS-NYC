<?php

class WPML_TM_REST_ATE_Jobs_Factory extends WPML_REST_Factory_Loader {

	public function create() {
		$ate_jobs_records = wpml_tm_get_ate_job_records();
		$ate_jobs         = new WPML_TM_ATE_Jobs( $ate_jobs_records );

		return new WPML_TM_REST_ATE_Jobs(
			$ate_jobs,
			wpml_tm_get_ate_jobs_repository()
		);
	}
}