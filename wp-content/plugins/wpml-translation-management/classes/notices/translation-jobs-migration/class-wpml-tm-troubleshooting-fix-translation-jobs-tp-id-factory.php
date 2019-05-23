<?php

class WPML_TM_Troubleshooting_Fix_Translation_Jobs_TP_ID_Factory implements IWPML_Backend_Action_Loader {

	public function create() {
		global $wpml_post_translations, $wpml_term_translations, $wpdb;

		$jobs_migration_repository = new WPML_Translation_Jobs_Migration_Repository( wpml_tm_get_jobs_repository(), true );
		$job_factory               = wpml_tm_load_job_factory();
		$wpml_tm_records           = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$cms_id_helper             = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );
		$jobs_migration            = new WPML_Translation_Jobs_Migration( $jobs_migration_repository, $cms_id_helper, $wpdb, wpml_tm_get_tp_jobs_api() );

		return new WPML_TM_Troubleshooting_Fix_Translation_Jobs_TP_ID( $jobs_migration, wpml_tm_get_jobs_repository() );
	}
}