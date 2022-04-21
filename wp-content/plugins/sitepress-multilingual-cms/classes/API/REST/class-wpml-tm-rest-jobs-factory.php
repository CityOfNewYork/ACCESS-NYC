<?php

class WPML_TM_REST_Jobs_Factory extends WPML_REST_Factory_Loader {
	/**
	 * @return WPML_TM_REST_Jobs
	 */
	public function create() {
		global $sitepress, $wpdb;

		return new WPML_TM_REST_Jobs(
			wpml_tm_get_jobs_repository(),
			new WPML_TM_Rest_Jobs_Criteria_Parser(),
			new WPML_TM_Rest_Jobs_View_Model(
				WPML_TM_Rest_Jobs_Translation_Service::create(),
				new WPML_TM_Rest_Jobs_Element_Info(
					new WPML_TM_Rest_Jobs_Package_Helper_Factory()
				),
				new WPML_TM_Rest_Jobs_Language_Names( $sitepress ),
				new WPML_TM_Rest_Job_Translator_Name(),
				new WPML_TM_Rest_Job_Progress()
			),
			new WPML_TP_Sync_Update_Job( $wpdb, $sitepress ),
			new WPML_TM_Last_Picked_Up( $sitepress )
		);
	}

}
