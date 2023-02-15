<?php

class WPML_TM_REST_TP_XLIFF_Factory extends WPML_REST_Factory_Loader {

	public function create() {
		return new WPML_TM_REST_TP_XLIFF(
			new WPML_TP_Translations_Repository(
				wpml_tm_get_tp_xliff_api(),
				wpml_tm_get_jobs_repository()
			),
			new WPML_TM_Rest_Download_File()
		);
	}
}
