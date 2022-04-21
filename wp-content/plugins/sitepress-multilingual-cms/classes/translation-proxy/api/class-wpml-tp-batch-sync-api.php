<?php

class WPML_TP_Batch_Sync_API extends WPML_TP_API {

	const INIT_SYNC    = '/batches/sync.json';
	const CHECK_STATUS = '/batches/sync/status.json';

	/**
	 * @param array $batch_ids
	 *
	 * @return int[]
	 * @throws WPML_TP_API_Exception
	 */
	public function init_synchronization( array $batch_ids ) {
		$request = new WPML_TP_API_Request( self::INIT_SYNC );
		$request->set_params(
			array(
				'batch_id'  => $batch_ids,
				'accesskey' => $this->project->get_access_key(),
			)
		);

		return $this->handle_response( $request );
	}

	/**
	 * @return int[]
	 * @throws WPML_TP_API_Exception
	 */
	public function check_progress() {
		$request = new WPML_TP_API_Request( self::CHECK_STATUS );
		$request->set_params( array( 'accesskey' => $this->project->get_access_key() ) );

		return $this->handle_response( $request );
	}

	/**
	 * @param WPML_TP_API_Request $request
	 *
	 * @return array
	 * @throws WPML_TP_API_Exception
	 */
	private function handle_response( WPML_TP_API_Request $request ) {
		$result = $this->client->send_request( $request, true );
		if ( ! $result || empty( $result ) || ! isset( $result->queued_batches ) ) {
			throw new WPML_TP_API_Exception( 'Batch synchronization could not be initialized' );
		}

		return array_map( 'intval', $result->queued_batches );
	}
}
