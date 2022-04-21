<?php

class WPML_TP_Sync_Ajax_Handler {

	const AJAX_ACTION = 'wpml-tp-sync-job-states';

	/** @var WPML_TP_Sync_Jobs */
	private $tp_sync;

	/** @var WPML_TM_Sync_Installer_Wrapper */
	private $installer_wrapper;

	/** @var WPML_TM_Last_Picked_Up $wpml_tm_last_picked_up */
	private $wpml_tm_last_picked_up;

	/**
	 * WPML_TP_Sync_Jobs constructor.
	 *
	 * @param WPML_TP_Sync_Jobs              $tp_sync
	 * @param WPML_TM_Sync_Installer_Wrapper $installer_wrapper
	 * @param WPML_TM_Last_Picked_Up         $wpml_tm_last_picked_up
	 */
	public function __construct(
		WPML_TP_Sync_Jobs $tp_sync,
		WPML_TM_Sync_Installer_Wrapper $installer_wrapper,
		WPML_TM_Last_Picked_Up $wpml_tm_last_picked_up
	) {
		$this->tp_sync                = $tp_sync;
		$this->installer_wrapper      = $installer_wrapper;
		$this->wpml_tm_last_picked_up = $wpml_tm_last_picked_up;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle' ) );
	}

	public function handle() {
		try {
			if ( isset( $_REQUEST['update_last_picked_up'] ) ) {
				$this->wpml_tm_last_picked_up->set();
			}

			if ( $this->installer_wrapper->is_wpml_registered() ) {
				$jobs = $this->tp_sync->sync();
				do_action( 'wpml_tm_empty_mail_queue' );
			} else {
				$jobs = new WPML_TM_Jobs_Collection( array() );
			}

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
