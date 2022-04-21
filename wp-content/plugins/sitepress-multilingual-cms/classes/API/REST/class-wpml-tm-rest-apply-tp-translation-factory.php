<?php

class WPML_TM_REST_Apply_TP_Translation_Factory extends WPML_REST_Factory_Loader {
	/**
	 * @return WPML_TM_REST_Apply_TP_Translation
	 */
	public function create() {
		global $wpdb;

		return new WPML_TM_REST_Apply_TP_Translation(
			new WPML_TP_Apply_Translations(
				wpml_tm_get_jobs_repository(),
				new WPML_TP_Apply_Single_Job(
					wpml_tm_get_tp_translations_repository(),
					new WPML_TP_Apply_Translation_Strategies( $wpdb )
				),
				wpml_tm_get_tp_sync_jobs()
			)
		);
	}
}
