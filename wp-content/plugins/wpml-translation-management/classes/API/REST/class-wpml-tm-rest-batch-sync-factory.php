<?php

class WPML_TM_REST_Batch_Sync_Factory extends WPML_REST_Factory_Loader {
	/**
	 * @return WPML_TM_REST_Batch_Sync
	 */
	public function create() {
		return new WPML_TM_REST_Batch_Sync(
			new WPML_TP_Batch_Sync_API(
				wpml_tm_get_tp_api_client(),
				wpml_tm_get_tp_project(),
				new WPML_TM_Log()
			)
		);
	}
}