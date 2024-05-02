<?php

class WPML_TP_Sync_Ajax_Handler {

	const AJAX_ACTION = 'wpml-tp-sync-job-states';

	/** @var WPML_TP_Sync_Jobs */
	private $tp_sync;

	/** @var WPML_TM_Last_Picked_Up $wpml_tm_last_picked_up */
	private $wpml_tm_last_picked_up;

	/**
	 * WPML_TP_Sync_Jobs constructor.
	 *
	 * @param WPML_TP_Sync_Jobs      $tp_sync
	 * @param WPML_TM_Last_Picked_Up $wpml_tm_last_picked_up
	 */
	public function __construct( WPML_TP_Sync_Jobs $tp_sync, WPML_TM_Last_Picked_Up $wpml_tm_last_picked_up ) {
		$this->tp_sync                = $tp_sync;
		$this->wpml_tm_last_picked_up = $wpml_tm_last_picked_up;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle' ) );
	}

	public function handle() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'sync-job-states' ) ) {
			wp_send_json_error( esc_html__( 'Invalid request!' ) );
		}

		try {
			if ( isset( $_REQUEST['update_last_picked_up'] ) ) {
				$this->wpml_tm_last_picked_up->set();
			}

			$jobs = $this->tp_sync->sync();
			do_action( 'wpml_tm_empty_mail_queue' );

			wp_send_json_success( $jobs->map( array( $this, 'map_job_to_result' ) ) );

			return true;
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 503 );

			return false;
		}
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return array
	 */
	public function map_job_to_result( WPML_TM_Job_Entity $job ) {
		return array(
			'id'                      => $job->get_id(),
			'type'                    => $job->get_type(),
			'status'                  => $job->get_status(),
			'hasCompletedTranslation' => $job->has_completed_translation(),
			'needsUpdate'             => $job->does_need_update(),
		);
	}
}
