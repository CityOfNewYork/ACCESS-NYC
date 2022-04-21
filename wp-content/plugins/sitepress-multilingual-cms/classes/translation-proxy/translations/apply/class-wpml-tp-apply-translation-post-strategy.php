<?php

class WPML_TP_Apply_Translation_Post_Strategy implements WPML_TP_Apply_Translation_Strategy {
	/** @var WPML_TP_Jobs_API */
	private $jobs_api;

	/** @var wpdb */
	private $wpdb;

	/**
	 * @param WPML_TP_Jobs_API $jobs_api
	 */
	public function __construct( WPML_TP_Jobs_API $jobs_api ) {
		$this->jobs_api = $jobs_api;

		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @param WPML_TM_Job_Entity             $job
	 * @param WPML_TP_Translation_Collection $translations
	 *
	 * @return void
	 * @throws WPML_TP_API_Exception
	 */
	public function apply( WPML_TM_Job_Entity $job, WPML_TP_Translation_Collection $translations ) {
		if ( ! $job instanceof WPML_TM_Post_Job_Entity ) {
			throw new InvalidArgumentException( 'A job must have post type' );
		}

		kses_remove_filters();
		wpml_tm_save_data( $this->build_data( $job, $translations ) );
		kses_init();

		$this->jobs_api->update_job_state( $job, 'delivered' );
	}

	/**
	 * @param WPML_TM_Job_Entity             $job
	 * @param WPML_TP_Translation_Collection $translations
	 *
	 * @return array
	 */
	private function build_data( WPML_TM_Post_Job_Entity $job, WPML_TP_Translation_Collection $translations ) {
		$data = array(
			'job_id'   => $job->get_translate_job_id(),
			'fields'   => array(),
			'complete' => 1
		);

		/** @var WPML_TP_Translation $translation */
		foreach ( $translations as $translation ) {
			foreach ( $job->get_elements() as $element ) {
				if ( $element->get_type() === $translation->get_field() ) {
					$data['fields'][ $element->get_type() ] = array(
						'data'       => $translation->get_target(),
						'finished'   => 1,
						'tid'        => $element->get_id(),
						'field_type' => $element->get_type(),
						'format'     => $element->get_format()
					);
				}
			}
		}

		return $data;
	}
}