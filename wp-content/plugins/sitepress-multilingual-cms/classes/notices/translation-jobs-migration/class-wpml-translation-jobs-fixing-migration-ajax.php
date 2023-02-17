<?php

class WPML_Translation_Jobs_Fixing_Migration_Ajax {

	const ACTION                    = 'wpml_translation_jobs_migration';
	const JOBS_MIGRATED_PER_REQUEST = 100;
	const PAGINATION_OPTION         = 'wpml_translation_jobs_migration_processed';

	/** @var WPML_Translation_Jobs_Migration  */
	private $jobs_migration;

	/** @var WPML_Translation_Jobs_Migration_Repository  */
	private $jobs_repository;

	/** @var WPML_TM_Jobs_Migration_State */
	private $migration_state;


	public function __construct(
		WPML_Translation_Jobs_Migration $jobs_migration,
		WPML_Translation_Jobs_Migration_Repository $jobs_repository,
		WPML_TM_Jobs_Migration_State $migration_state
	) {
		$this->jobs_migration  = $jobs_migration;
		$this->jobs_repository = $jobs_repository;
		$this->migration_state = $migration_state;
	}

	public function run_migration() {
		if ( ! $this->is_valid_request() ) {
			wp_send_json_error();
		}

		$jobs       = $this->jobs_repository->get();
		$total_jobs = count( $jobs );

		$offset = $this->get_already_processed();

		if ( $offset < $total_jobs ) {
			$jobs_chunk = array_slice( $jobs, $offset, self::JOBS_MIGRATED_PER_REQUEST );

			try {
				$this->jobs_migration->migrate_jobs( $jobs_chunk, true );
			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage(), 500 );

				return;
			}

			$done             = $total_jobs <= $offset + self::JOBS_MIGRATED_PER_REQUEST;
			$jobs_chunk_total = count( $jobs_chunk );

			update_option( self::PAGINATION_OPTION, $offset + self::JOBS_MIGRATED_PER_REQUEST );
		} else {
			$done             = true;
			$jobs_chunk_total = 0;
		}

		$result = array(
			'totalJobs'    => $total_jobs,
			'jobsMigrated' => $jobs_chunk_total,
			'done'         => $done,
		);

		if ( $done ) {
			$this->migration_state->mark_fixing_migration_as_done();
			delete_option( self::PAGINATION_OPTION );
		}

		wp_send_json_success( $result );
	}

	/**
	 * @return bool
	 */
	private function is_valid_request() {
		return wp_verify_nonce( $_POST['nonce'], self::ACTION );
	}

	/**
	 * @return int
	 */
	private function get_already_processed() {
		return (int) get_option( self::PAGINATION_OPTION, 0 );
	}
}
