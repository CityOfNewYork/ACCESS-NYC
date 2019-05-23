<?php

class WPML_Translation_Jobs_Migration_Hooks_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	/**
	 * It creates an instance of WPML_Translation_Jobs_Migration_Notice.
	 *
	 * @return null|WPML_Translation_Jobs_Migration_Hooks|WPML_TM_Restore_Skipped_Migration
	 */
	public function create() {
		$fixing_migration = false;

		$wpml_notices = wpml_get_admin_notices();
		$wpml_notices->remove_notice( WPML_Translation_Jobs_Migration_Notice::NOTICE_GROUP_ID, 'all-translation-jobs-migration' );
		$wpml_notices->remove_notice( WPML_Translation_Jobs_Migration_Notice::NOTICE_GROUP_ID, 'translation-jobs-migration' );

		$migration_state = new WPML_TM_Jobs_Migration_State();
		if ( $migration_state->is_skipped() ) {
			return new WPML_TM_Restore_Skipped_Migration( $migration_state );
		}

		if ( $migration_state->is_migrated() ) {
			if ( $migration_state->is_fixing_migration_done() ) {
				return null;
			}

			$fixing_migration = true;
		}

		$template_service = new WPML_Twig_Template_Loader( array( WPML_TM_PATH . '/templates/translation-jobs-migration/' ) );

		if ( $fixing_migration ) {
			$notice = new WPML_All_Translation_Jobs_Migration_Notice( $wpml_notices, $template_service->get_template() );
		} else {
			$notice = new WPML_Translation_Jobs_Missing_TP_ID_Migration_Notice( $wpml_notices, $template_service->get_template() );
		}

		$jobs_migration_repository = new WPML_Translation_Jobs_Migration_Repository( wpml_tm_get_jobs_repository(), $fixing_migration );

		global $wpml_post_translations, $wpml_term_translations, $wpdb;

		$job_factory     = wpml_tm_load_job_factory();
		$wpml_tm_records = new WPML_TM_Records( $wpdb, $wpml_post_translations, $wpml_term_translations );
		$cms_id_helper   = new WPML_TM_CMS_ID( $wpml_tm_records, $job_factory );
		$jobs_migration  = new WPML_Translation_Jobs_Migration( $jobs_migration_repository, $cms_id_helper, $wpdb, wpml_tm_get_tp_jobs_api() );
		if ( $fixing_migration ) {
			$ajax_handler = new WPML_Translation_Jobs_Fixing_Migration_Ajax(
				$jobs_migration,
				$jobs_migration_repository,
				$migration_state
			);
		} else {
			$ajax_handler = new WPML_Translation_Jobs_Migration_Ajax(
				$jobs_migration,
				$jobs_migration_repository,
				$migration_state
			);
		}

		return new WPML_Translation_Jobs_Migration_Hooks(
			$notice,
			$ajax_handler,
			$jobs_migration_repository,
			wpml_get_upgrade_schema(),
			$migration_state
		);
	}
}
