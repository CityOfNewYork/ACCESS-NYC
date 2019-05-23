<?php

class WPML_TP_Apply_Translation_String_Strategy implements WPML_TP_Apply_Translation_Strategy {
	/** @var WPML_TP_Jobs_API */
	private $jobs_api;

	/** @var wpdb */
	private $wpdb;

	/**
	 * @param WPML_TP_Jobs_API $jobs_api
	 * @param wpdb             $wpdb
	 */
	public function __construct( WPML_TP_Jobs_API $jobs_api, wpdb $wpdb ) {
		$this->jobs_api = $jobs_api;
		$this->wpdb     = $wpdb;
	}

	/**
	 * @param WPML_TM_Job_Entity             $job
	 * @param WPML_TP_Translation_Collection $translations
	 *
	 * @return void
	 * @throws WPML_TP_API_Exception
	 */
	public function apply( WPML_TM_Job_Entity $job, WPML_TP_Translation_Collection $translations ) {
		if ( ! icl_translation_add_string_translation(
			$job->get_tp_id(),
			$this->map_translations_to_legacy_array( $translations ),
			$translations->get_target_language() )
		) {
			throw new WPML_TP_API_Exception( 'Could not apply string translation!' );
		}

		$this->update_local_job_status( $job, ICL_TM_COMPLETE );
		$this->jobs_api->update_job_state( $job, 'delivered' );
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 * @param int                $status
	 */
	private function update_local_job_status( WPML_TM_Job_Entity $job, $status ) {
		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_core_status',
			array( 'status' => $status ),
			array( 'id' => $job->get_id() )
		);
	}

	private function map_translations_to_legacy_array( WPML_TP_Translation_Collection $translations ) {
		$result = array();

		/** @var WPML_TP_Translation $translation */
		foreach ( $translations as $translation ) {
			$result[ $translation->get_field() ] = $translation->get_target();
		}

		return $result;
	}
}