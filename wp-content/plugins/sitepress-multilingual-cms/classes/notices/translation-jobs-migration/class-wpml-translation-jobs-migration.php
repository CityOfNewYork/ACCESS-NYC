<?php

class WPML_Translation_Jobs_Migration {

	const MIGRATION_FIX_LOG_KEY = 'wpml_fixing_migration_log';

	private $jobs_repository;
	private $cms_id_builder;
	private $wpdb;
	private $jobs_api;

	public function __construct(
		WPML_Translation_Jobs_Migration_Repository $jobs_repository,
		WPML_TM_CMS_ID $cms_id_builder,
		wpdb $wpdb,
		WPML_TP_Jobs_API $jobs_api
	) {
		$this->jobs_repository = $jobs_repository;
		$this->cms_id_builder  = $cms_id_builder;
		$this->wpdb            = $wpdb;
		$this->jobs_api        = $jobs_api;
	}

	/**
	 * @param WPML_TM_Post_Job_Entity[] $jobs
	 * @param bool                      $recover_status
	 *
	 * @throws WPML_TP_API_Exception
	 */
	public function migrate_jobs( array $jobs, $recover_status = false ) {
		$mapped_jobs = $this->map_cms_id_job_id( $jobs );

		if ( $mapped_jobs ) {
			$tp_jobs = $this->get_tp_jobs( $mapped_jobs );

			foreach ( $jobs as $job ) {
				$cms_id                      = array_key_exists( $job->get_id(), $mapped_jobs ) ? $mapped_jobs[ $job->get_id() ] : '';
				list( $tp_id, $revision_id ) = $this->get_tp_id_revision_id( $cms_id, $tp_jobs );

				if ( $recover_status ) {
					$this->recovery_mode( $job, $tp_id, $revision_id );
				} else {
					$this->first_migration_mode( $job, $tp_id, $revision_id );
				}
			}
		}
	}

	/**
	 * @param array $mapped_jobs
	 *
	 * @throws WPML_TP_API_Exception
	 * @return array
	 */
	private function get_tp_jobs( array $mapped_jobs ) {
		return $this->get_latest_jobs_grouped_by_cms_id(
			$this->jobs_api->get_jobs_per_cms_ids( array_values( $mapped_jobs ), true )
		);
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 * @param int                     $tp_id
	 * @param int                     $revision_id
	 */
	private function recovery_mode( WPML_TM_Post_Job_Entity $job, $tp_id, $revision_id ) {
		if ( $tp_id !== $job->get_tp_id() ) {
			$new_status = $this->get_new_status( $job, $tp_id );
			$this->log( $job->get_id(), $job->get_tp_id(), $tp_id, $job->get_status(), $new_status );

			$this->fix_job_fields( $tp_id, $revision_id, $new_status, $job->get_id() );
		}
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 * @param int                     $tp_id
	 * @param int                     $revision_id
	 */
	private function first_migration_mode( WPML_TM_Post_Job_Entity $job, $tp_id, $revision_id ) {
		$this->fix_job_fields( $tp_id, $revision_id, false, $job->get_id() );
	}

	/**
	 * @param array $tp_jobs
	 *
	 * @return array
	 */
	private function get_latest_jobs_grouped_by_cms_id( $tp_jobs ) {
		$result = array();

		foreach ( $tp_jobs as $tp_job ) {
			if ( ! isset( $result[ $tp_job->cms_id ] ) ) {
				$result[ $tp_job->cms_id ] = $tp_job;
			} elseif ( $tp_job->id > $result[ $tp_job->cms_id ]->id ) {
				$result[ $tp_job->cms_id ] = $tp_job;
			}
		}

		return $result;
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 * @param int                     $new_tp_id
	 *
	 * @return bool
	 */
	private function get_new_status( WPML_TM_Post_Job_Entity $job, $new_tp_id ) {
		$new_status = false;
		if ( $job->get_tp_id() !== null && $new_tp_id ) {
			if ( $job->get_status() === ICL_TM_NOT_TRANSLATED || $this->has_been_completed_after_release( $job ) ) {
				$new_status = ICL_TM_IN_PROGRESS;
			}
		}

		return $new_status;
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function has_been_completed_after_release( WPML_TM_Post_Job_Entity $job ) {
		return $job->get_status() === ICL_TM_COMPLETE && $job->get_completed_date() && $job->get_completed_date() > $this->get_4_2_0_release_date();
	}

	/**
	 * @param int   $cms_id
	 * @param array $tp_jobs
	 *
	 * @return array
	 */
	private function get_tp_id_revision_id( $cms_id, $tp_jobs ) {
		$result = array( 0, 0 );
		if ( isset( $tp_jobs[ $cms_id ] ) ) {
			$result = array(
				$tp_jobs[ $cms_id ]->id,
				$tp_jobs[ $cms_id ]->translation_revision,
			);
		}

		return $result;
	}

	/**
	 * @param int       $tp_id
	 * @param int       $revision_id
	 * @param int|false $status
	 * @param int       $job_id
	 */
	private function fix_job_fields( $tp_id, $revision_id, $status, $job_id ) {
		$new_data = array(
			'tp_id'       => $tp_id,
			'tp_revision' => $revision_id,
		);

		if ( $status ) {
			$new_data['status'] = $status;
		}

		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_translation_status',
			$new_data,
			array( 'rid' => $job_id )
		);
	}

	/**
	 * @param WPML_TM_Post_Job_Entity[] $jobs
	 *
	 * @return array
	 */
	private function map_cms_id_job_id( $jobs ) {
		$mapped_jobs = array();

		foreach ( $jobs as $job ) {
			$cms_id                        = $this->cms_id_builder->cms_id_from_job_id( $job->get_translate_job_id() );
			$mapped_jobs[ $job->get_id() ] = $cms_id;
		}

		return $mapped_jobs;
	}

	/**
	 * @return DateTime
	 * @throws Exception
	 */
	private function get_4_2_0_release_date() {
		return new DateTime(
			defined( 'WPML_4_2_0_RELEASE_DATE' ) ? WPML_4_2_0_RELEASE_DATE : '2019-01-20'
		);
	}

	/**
	 * @param int $rid
	 * @param int $old_tp_id
	 * @param int $new_tp_id
	 * @param int $old_status
	 * @param int $new_status
	 */
	private function log( $rid, $old_tp_id, $new_tp_id, $old_status, $new_status ) {
		$log = get_option( self::MIGRATION_FIX_LOG_KEY, array() );
		$key = $new_status ? 'status_changed' : 'status_not_changed';

		$log[ $key ][ $rid ] = array(
			'rid'        => $rid,
			'old_tp_id'  => $old_tp_id,
			'new_tp_id'  => $new_tp_id,
			'old_status' => $old_status,
			'new_status' => $new_status,
		);

		update_option( self::MIGRATION_FIX_LOG_KEY, $log, false );
	}
}
