<?php

class WPML_TP_Sync_Update_Job {

	private $strategies = array(
		WPML_TM_Job_Entity::POST_TYPE    => 'update_post_job',
		WPML_TM_Job_Entity::STRING_TYPE  => 'update_string_job',
		WPML_TM_Job_Entity::PACKAGE_TYPE => 'update_post_job',
	);

	/** @var wpdb */
	private $wpdb;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 */
	public function update_state( WPML_TM_Job_Entity $job ) {
		if ( ! array_key_exists( $job->get_type(), $this->strategies ) ) {
			return $job;
		}

		$method = $this->strategies[ $job->get_type() ];

		return call_user_func( array( $this, $method ), $job );
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 */
	private function update_string_job( WPML_TM_Job_Entity $job ) {
		if ( $job->get_tp_id() ) {
			$this->wpdb->update(
				$this->wpdb->prefix . 'icl_core_status',
				array(
					'status'      => $job->get_status(),
					'tp_revision' => $job->get_revision(),
					'ts_status'   => $this->get_ts_status_in_ts_format( $job ),
				),
				array( 'rid' => $job->get_tp_id() ) );
		}

		$data = array(
			'status' => $job->get_status(),
		);
		if ( ICL_TM_NOT_TRANSLATED === $job->get_status() ) {
			$data['translator_id'] = null;
		}

		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_string_translations',
			$data,
			array( 'id' => $job->get_id() )
		);

		icl_update_string_status( $job->get_original_element_id() );

		return $job;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 */
	private function update_post_job( WPML_TM_Job_Entity $job ) {
		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_translation_status',
			array(
				'status'      => $job->get_status(),
				'tp_revision' => $job->get_revision(),
				'ts_status'   => $this->get_ts_status_in_ts_format( $job ),
			),
			array( 'rid' => $job->get_id() )
		);

		return $job;
	}

	/**
	 * In the db, we store the exact json format that we get from TS. It includes an extra ts_status key
	 *
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return string
	 */
	private function get_ts_status_in_ts_format( WPML_TM_Job_Entity $job ) {
		$ts_status = $job->get_ts_status();

		return $ts_status ? wp_json_encode( array( 'ts_status' => json_decode( (string) $ts_status ) ) ) : null;
	}
}