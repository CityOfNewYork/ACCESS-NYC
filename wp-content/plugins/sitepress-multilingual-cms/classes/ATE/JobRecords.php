<?php

namespace WPML\TM\ATE;

use Exception;
use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML_TM_ATE_API_Error;
use WPML_TM_Editors;

class JobRecords {

	const FIELD_ATE_JOB_ID = 'ate_job_id';
	const FIELD_IS_EDITING = 'is_editing';

	/** @var \wpdb $wpdb */
	private $wpdb;

	/** @var Collection $jobs */
	private $jobs;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
		$this->jobs = wpml_collect( [] );
	}

	/**
	 * This method will retrieve data from the ATE job ID.
	 * Beware of the returned data shape which is not standard.
	 *
	 * @param int $ateJobId
	 *
	 * @return array|null
	 */
	public function get_data_from_ate_job_id( $ateJobId ) {
		$ateJobId = (int) $ateJobId;

		$this->warmCache( [], [ $ateJobId ] );

		$job = $this->jobs->first(
			function( JobRecord $job ) use ( $ateJobId ) {
				return $job->ateJobId === $ateJobId;
			}
		);

		if ( $job ) {
			/** @var JobRecord $job */
			return [
				'wpml_job_id'  => $job->wpmlJobId,
				'ate_job_data' => [
					'ate_job_id' => $job->ateJobId,
				],
			];
		}

		return null;
	}

	/**
	 * @param int   $wpmlJobId
	 * @param array $ateJobData
	 */
	public function store( $wpmlJobId, array $ateJobData ) {
		$ateJobData['job_id'] = (int) $wpmlJobId;

		$this->warmCache( [ $wpmlJobId ] );
		$job = $this->jobs->get( $wpmlJobId );

		if ( ! $job ) {
			$job            = new JobRecord();
			$job->wpmlJobId = (int) $wpmlJobId;
		}

		if ( isset( $ateJobData[ self::FIELD_ATE_JOB_ID ] ) ) {
			$job->ateJobId = $ateJobData[ self::FIELD_ATE_JOB_ID ];
		}

		$this->persist( $job );
	}

	/**
	 * @param JobRecord $job
	 */
	public function persist( JobRecord $job ) {
		$this->jobs->put( $job->wpmlJobId, $job );

		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_translate_job',
			[ 'editor_job_id' => $job->ateJobId ],
			[ 'job_id' => $job->wpmlJobId ],
			[ '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * This method will load in-memory the required jobs.
	 *
	 * @param array $wpmlJobIds
	 * @param array $ateJobIds
	 */
	public function warmCache( array $wpmlJobIds, array $ateJobIds = [] ) {
		$wpmlJobIds = wpml_collect( $wpmlJobIds )->reject( $this->isAlreadyLoaded( 'wpmlJobId' ) )->toArray();
		$ateJobIds  = wpml_collect( $ateJobIds )->reject( $this->isAlreadyLoaded( 'ateJobId' ) )->toArray();

		$where = [];

		if ( $wpmlJobIds ) {
			$where[] = 'job_id IN(' . wpml_prepare_in( $wpmlJobIds, '%d' ) . ')';
		}

		if ( $ateJobIds ) {
			$where[] = 'editor_job_id IN(' . wpml_prepare_in( $ateJobIds, '%d' ) . ')';
		}

		if ( ! $where ) {
			return;
		}

		$whereHasJobIds = implode( ' OR ', $where );

		$rows = $this->wpdb->get_results(
			"
			SELECT job_id, editor_job_id
			FROM {$this->wpdb->prefix}icl_translate_job
			WHERE editor = '" . WPML_TM_Editors::ATE . "' AND ({$whereHasJobIds})
		"
		);

		foreach ( $rows as $row ) {
			$job = new JobRecord( $row );
			$this->jobs->put( $job->wpmlJobId, $job );
		}
	}

	/**
	 * @param $idPropertyName
	 *
	 * @return \Closure
	 */
	private function isAlreadyLoaded( $idPropertyName ) {
		$loadedIds = $this->jobs->pluck( $idPropertyName )->values()->toArray();

		return Lst::includes( Fns::__, $loadedIds );
	}

	/**
	 * @param int $wpmlJobId
	 *
	 * @return int
	 */
	public function get_ate_job_id( $wpmlJobId ) {
		return $this->get( $wpmlJobId )->ateJobId;
	}

	/**
	 * @param int $wpmlJobId
	 *
	 * @return bool
	 */
	public function is_editing_job( $wpmlJobId ) {
		return $this->get( $wpmlJobId )->isEditing();
	}

	/**
	 * @param $wpmlJobId
	 *
	 * @return JobRecord
	 */
	public function get( $wpmlJobId ) {
		if ( ! $this->jobs->has( $wpmlJobId ) ) {
			$this->warmCache( [ (int) $wpmlJobId ] );
		}

		/** @var null|JobRecord $job */
		$job = $this->jobs->get( $wpmlJobId );

		if ( ! $job || ! $job->ateJobId ) {
			$this->restoreJobDataFromATE( $wpmlJobId );
			$job = $this->jobs->get( $wpmlJobId, new JobRecord() );
		}

		return $job;
	}

	/**
	 * This method will try to recover the job data from ATE server,
	 * and persist it in the local repository.
	 *
	 * @param int $wpmlJobId
	 */
	private function restoreJobDataFromATE( $wpmlJobId ) {
		$data = apply_filters( 'wpml_tm_ate_job_data_fallback', [], $wpmlJobId );

		if ( $data ) {
			try {
				$this->store( $wpmlJobId, $data );
			} catch ( Exception $e ) {
				$error_log = new WPML_TM_ATE_API_Error();
				$error_log->log( $e->getMessage() );
			}
		}
	}
}
