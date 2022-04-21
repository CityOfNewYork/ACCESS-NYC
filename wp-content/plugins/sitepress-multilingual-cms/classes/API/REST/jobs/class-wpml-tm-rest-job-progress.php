<?php

class WPML_TM_Rest_Job_Progress {
	/** @var wpdb */
	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return string
	 */
	public function get( WPML_TM_Job_Entity $job ) {
		if ( $job->get_translation_service() !== 'local' ) {
			return '';
		}

		if ( $job->get_status() !== ICL_TM_IN_PROGRESS ) {
			return '';
		}

		if ( $job->get_type() === WPML_TM_Job_Entity::STRING_TYPE ) {
			return '';
		}

		$sql = "
			SELECT field_finished FROM {$this->wpdb->prefix}icl_translate translate
			INNER JOIN {$this->wpdb->prefix}icl_translate_job translate_job ON translate_job.job_id = translate.job_id
			INNER JOIN {$this->wpdb->prefix}icl_translation_status translation_status ON translation_status.rid = translate_job.rid
			WHERE translation_status.rid = %d AND translate.field_translate  = 1 AND LENGTH(translate.field_data) > 0
		";
		$sql = $this->wpdb->prepare( $sql, $job->get_id() );

		$elements   = $this->wpdb->get_col( $sql );
		$translated = array_filter( $elements );

		$percentage = (int) ( count( $translated ) / count( $elements ) * 100 );

		return sprintf( _x( '%s completed', 'Translation jobs list', 'wpml-transation-manager' ), $percentage . '%' );
	}
}
