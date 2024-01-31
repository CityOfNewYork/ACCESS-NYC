<?php

namespace WPML\TM\ATE\Retranslation;

use WPML\FP\Obj;
use WPML\TM\ATE\Retranslation\JobsCollector\ATEResponse;

/**
 * The class calls ATE endpoint to get list of the jobs that have to be re-translated.
 */
class JobsCollector {

	/** @var \WPML_TM_ATE_API */
	private $ateAPI;

	public function __construct( \WPML_TM_ATE_API $ateAPI ) {
		$this->ateAPI = $ateAPI;
	}


	public function get( int $page = 1 ): ATEResponse {
		$result = $this->ateAPI->get_jobs_to_retranslation( $page );

		return $result->map( function ( $result ) {
			return new ATEResponse(
				Obj::propOr( false, 'retranslation_finished', $result),
				Obj::propOr( [], 'job_ids', $result ),
				Obj::propOr( 0, 'page', $result ),
				Obj::propOr( 0, 'pages', $result )
			);
		} )->getOrElse( new ATEResponse( false, [], 0, 0 ) );
	}

}
