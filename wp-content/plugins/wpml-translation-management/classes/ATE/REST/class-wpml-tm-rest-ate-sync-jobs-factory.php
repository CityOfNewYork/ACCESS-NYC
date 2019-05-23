<?php

class WPML_TM_REST_ATE_Sync_Jobs_Factory extends WPML_REST_Factory_Loader {
	public function create() {
		$jobs_action_factory = new WPML_TM_ATE_Jobs_Actions_Factory();
		$jobs_action         = $jobs_action_factory->create();

		if ( $jobs_action ) {
			return new WPML_TM_REST_ATE_Sync_Jobs(
				wpml_load_core_tm(),
				$jobs_action
			);
		}

		return null;
	}
}