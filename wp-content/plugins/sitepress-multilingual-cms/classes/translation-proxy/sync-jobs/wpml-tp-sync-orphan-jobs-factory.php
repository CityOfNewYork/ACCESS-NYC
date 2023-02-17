<?php

class WPML_TP_Sync_Orphan_Jobs_Factory {
	/**
	 * @return WPML_TP_Sync_Orphan_Jobs
	 */
	public function create() {
		global $wpdb, $sitepress;

		return new WPML_TP_Sync_Orphan_Jobs(
			wpml_tm_get_jobs_repository(),
			new WPML_TP_Sync_Update_Job( $wpdb, $sitepress )
		);
	}
}
