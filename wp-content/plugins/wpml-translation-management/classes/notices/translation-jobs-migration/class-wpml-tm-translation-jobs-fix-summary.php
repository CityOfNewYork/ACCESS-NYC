<?php

class WPML_TM_Translation_Jobs_Fix_Summary {

	const INVALID_JOBS_SYNCED_KEY = 'wpml_tm_migration_invalid_jobs_already_synced';

	/** @var WPML_TM_Translation_Jobs_Fix_Summary_Notice  */
	private $notice;

	/** @var WPML_TM_Jobs_Migration_State */
	private $migration_state;

	public function __construct(
		WPML_TM_Translation_Jobs_Fix_Summary_Notice $notice,
		WPML_TM_Jobs_Migration_State $migration_state
	) {
		$this->notice = $notice;
		$this->migration_state = $migration_state;
	}

	public function add_hooks() {
		add_action( 'init', array( $this, 'display_summary' ) );
		add_action( 'wp_ajax_' . WPML_TP_Sync_Ajax_Handler::AJAX_ACTION, array(
			$this,
			'mark_invalid_jobs_as_synced'
		) );
	}

	public function display_summary() {
		if ( $this->should_display_summary_notice() ) {
			$this->notice->add_notice();
		} elseif ( $this->notice->exists() ) {
			$this->notice->remove_notice();
		}
	}

	private function should_display_summary_notice() {
		if ( ! $this->migration_state->is_fixing_migration_done() ) {
			return false;
		}

		$jobs_with_new_status = get_option( WPML_Translation_Jobs_Migration::MIGRATION_FIX_LOG_KEY );
		$jobs_with_new_status = isset( $jobs_with_new_status['status_changed'] ) ? $jobs_with_new_status['status_changed'] : null;
		$jobs_already_synced  = (int) get_option( self::INVALID_JOBS_SYNCED_KEY ) > 1;

		return $jobs_with_new_status && ! $jobs_already_synced;
	}

	public function mark_invalid_jobs_as_synced() {
		update_option( self::INVALID_JOBS_SYNCED_KEY, 2 );
	}
}