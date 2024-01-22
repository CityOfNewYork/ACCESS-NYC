<?php

use WPML\FP\Obj;

class WPML_TP_Jobs_API extends WPML_TP_API {

	const CHUNK_SIZE = 100;

	/**
	 * @param int[] $tp_job_ids
	 *
	 * @return WPML_TP_Job_Status[]
	 * @throws WPML_TP_API_Exception
	 */
	public function get_jobs_statuses( array $tp_job_ids ) {
		$this->log( 'Get jobs status', $tp_job_ids );

		$chunks = array();

		while ( $tp_job_ids ) {
			$chunk_ids = array_splice( $tp_job_ids, 0, self::CHUNK_SIZE );
			$chunks[]  = $this->get_chunk_of_job_statuses( $chunk_ids );
		}

		$result = call_user_func_array( 'array_merge', $chunks );

		return array_map( array( $this, 'build_job_status' ), $result );
	}

	private function get_chunk_of_job_statuses( $tp_job_ids ) {
		$request = new WPML_TP_API_Request( '/jobs.json' );
		$request->set_params(
			array(
				'filter'    => array(
					'job_ids'  => array_filter( $tp_job_ids, 'is_int' ),
					'archived' => 1,
				),
				'accesskey' => $this->project->get_access_key(),
			)
		);

		return $this->client->send_request( $request );
	}

	/**
	 * @param array $cms_ids
	 * @param bool  $archived
	 *
	 * @return array|mixed|stdClass|string
	 * @throws WPML_TP_API_Exception
	 */
	public function get_jobs_per_cms_ids( array $cms_ids, $archived = false ) {
		$request = new WPML_TP_API_Request( '/jobs.json' );

		$filter = array(
			'filter'    => array(
				'cms_ids' => $cms_ids,
			),
			'accesskey' => $this->project->get_access_key(),
		);

		if ( $archived ) {
			$filter['filter']['archived'] = (int) $archived;
			$request->set_params( $filter );
		}

		return $this->client->send_request( $request );
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 * @param string             $state
	 * @param string             $post_url
	 *
	 * @throws WPML_TP_API_Exception
	 */
	public function update_job_state(
		WPML_TM_Job_Entity $job,
		$state = WPML_TP_Job_States::DELIVERED,
		$post_url = null
	) {
		$params = array(
			'job_id'     => $job->get_tp_id(),
			'project_id' => $this->project->get_id(),
			'accesskey'  => $this->project->get_access_key(),
			'job'        => array(
				'state' => $state,
			),
		);
		if ( $post_url ) {
			$params['job']['url'] = $post_url;
		}

		$request = new WPML_TP_API_Request( '/jobs/{job_id}.json' );
		$request->set_params( $params );
		$request->set_method( 'PUT' );

		$this->client->send_request( $request );
	}

	private function build_job_status( stdClass $raw_data ) {
		return new WPML_TP_Job_Status(
			$raw_data->id,
			isset( $raw_data->batch->id ) ? $raw_data->batch->id : 0,
			$raw_data->job_state,
			isset( $raw_data->translation_revision ) ? $raw_data->translation_revision : 1,
			$raw_data->ts_status ? new WPML_TM_Job_TS_Status(
				$raw_data->ts_status->status,
				$raw_data->ts_status->links
			) : null
		);
	}

	/**
	 * @return WPML_TP_Job_Status[]
	 * @throws WPML_TP_API_Exception
	 */
	public function get_revised_jobs() {
		$this->log( 'Get revised jobs' );

		$request = new WPML_TP_API_Request( '/jobs.json' );
		$request->set_params(
			array(
				'filter'    => array(
					'job_state'             => WPML_TP_Job_States::TRANSLATION_READY,
					'archived'              => 0,
					'revision_greater_than' => 1,
				),
				'accesskey' => $this->project->get_access_key(),
			)
		);

		$result = $this->client->send_request( $request );

		return array_map( array( $this, 'build_job_status' ), $result );
	}

	/**
	 * @param int $tp_job_id
	 *
	 * @return string
	 * @throws WPML_TP_API_Exception When response is incorrect.
	 */
	public function get_translated_xliff_download_url( $tp_job_id ) {
		$request = new WPML_TP_API_Request( '/jobs.json' );

		$params = [
			'filter'    => [
				'job_ids' => $tp_job_id,
			],
			'accesskey' => $this->project->get_access_key(),
		];
		$request->set_params( $params );

		$result = $this->client->send_request( $request );
		if ( ! $result || ! is_array( $result ) || 0 === count( $result ) || ! is_object( $result[0] ) ) {
			throw new WPML_TP_API_Exception( 'XLIFF download link could not be fetched for tp_job: ' . $tp_job_id, $request );
		}

		return Obj::propOr( '', 'translated_xliff_download_url', $result[0] );
	}
}
