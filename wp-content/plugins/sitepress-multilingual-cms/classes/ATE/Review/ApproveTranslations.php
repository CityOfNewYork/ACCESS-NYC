<?php

namespace WPML\TM\ATE\Review;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\API\Jobs;
use function WPML\Container\make;

class ApproveTranslations implements IHandler {

	public function run( Collection $data ) {
		$jobIds = $data->get( 'jobsIds' );

		return wpml_collect( $jobIds )
			->map( Jobs::get() )
			->filter( ReviewStatus::doesJobNeedReview() )
			->map( Obj::addProp( 'translated_id', Jobs::getTranslatedPostId() ) )
			->map( Obj::props( [ 'job_id', 'translated_id' ] ) )
			->map( Lst::zipObj( [ 'jobId', 'postId' ] ) )
			->map( 'wpml_collect' )
			->map( [ make( AcceptTranslation::class ), 'run' ] )
			->map( function ( Either $result ) {
				$isJobApproved = Fns::isRight( $result );
				$jobId         = $result->coalesce( Fns::identity(), Fns::identity() )->getOrElse( 0 );

				return [ 'jobId' => $jobId, 'status' => $isJobApproved ];
			} );
	}
}
