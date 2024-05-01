<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use function WPML\Container\make;
use function WPML\FP\invoke;

class CancelJobs implements IHandler {

	public function run( Collection $data ) {
		if ( $data->get( 'getTotal', false ) ) {
			return Either::of( wpml_tm_get_jobs_repository()->get_count( $this->getSearchParams() ) );
		}

		$batchSize = $data->get( 'batchSize', 1000 );
		$params   = $this->getSearchParams()->set_limit( $batchSize );

		$toCancel = wpml_collect( wpml_tm_get_jobs_repository()->get( $params ) );
		$toCancel->map( Fns::tap( invoke( 'set_status' )->with( ICL_TM_NOT_TRANSLATED ) ) )
		         ->map( Fns::tap( [ make( \WPML_TP_Sync_Update_Job::class ), 'update_state' ] ) );

		return Either::of( $toCancel->count() );
	}

	/**
	 * @return \WPML_TM_Jobs_Search_Params
	 */
	private function getSearchParams() {
		$searchParams = new \WPML_TM_Jobs_Search_Params();
		$searchParams->set_status( [ ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS ] );
		$searchParams->set_custom_where_conditions( [ 'translate_job.automatic = 1' ] );

		return $searchParams;
	}
}

