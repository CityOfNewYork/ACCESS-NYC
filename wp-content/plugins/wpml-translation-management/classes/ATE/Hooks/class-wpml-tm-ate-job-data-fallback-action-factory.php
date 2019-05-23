<?php

class WPML_TM_ATE_Job_Data_Fallback_Factory implements IWPML_Backend_Action_Loader, IWPML_REST_Action_Loader {
	/**
	 * @return WPML_TM_ATE_Job_Data_Fallback
	 */
	public function create() {
		$jobs_action_factory = new WPML_TM_ATE_Jobs_Actions_Factory();

		return new WPML_TM_ATE_Job_Data_Fallback( $jobs_action_factory->create_ate_api() );
	}

}