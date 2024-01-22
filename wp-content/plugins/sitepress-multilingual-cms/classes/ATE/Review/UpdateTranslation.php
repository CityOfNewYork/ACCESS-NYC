<?php

namespace WPML\TM\ATE\Review;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\API\ATE;
use WPML\TM\API\Jobs;
use function WPML\Container\make;
use function WPML\FP\partial;
use function WPML\FP\pipe;

class UpdateTranslation implements IHandler {
	public function run( Collection $data ) {
		$jobId            = $data->get( 'jobId' );
		$postId           = $data->get( 'postId' );
		$completedInATE   = $data->get( 'completedInATE' );
		$clickedBackInATE = $data->get( 'clickedBackInATE' );

		if ( $completedInATE === 'COMPLETED_WITHOUT_CHANGED' || $clickedBackInATE ) {
			return $this->completeWithoutChanges( $jobId );
		}

		$ateAPI           = make( ATE::class );
		$applyTranslation = pipe(
			partial( [ $ateAPI, 'applyTranslation' ], $jobId, $postId ),
			Logic::ifElse(
				Fns::identity(),
				Fns::tap( function () use ( $jobId ) {
					Jobs::setReviewStatus( $jobId, ReviewStatus::NEEDS_REVIEW );
				} ),
				Fns::identity()
			)
		);

		$hasStatus = function ( $statuses ) {
			return pipe( Obj::prop( 'status_id' ), Lst::includes( Fns::__, $statuses ) );
		};

		$shouldApplyXLIFF = $hasStatus( [
			\WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_DELIVERING,
			\WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_TRANSLATED,
			\WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_EDITED,
		] );

		$applyXLIFF = Logic::ifElse(
			pipe( Obj::prop( 'translated_xliff' ), $applyTranslation ),
			Fns::always( Either::of( 'applied' ) ),
			Fns::always( Either::left( 'error' ) )
		);

		$isDelivered              = $hasStatus( [ \WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_DELIVERED ] );
		$isTranslating            = $hasStatus( [ \WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_TRANSLATING ] );
		$userClickedCompleteInATE = Fns::always( $completedInATE === 'COMPLETED' );
		$otherwise                = Fns::always( true );

		$handleATEResult = Logic::cond( [
			[ $shouldApplyXLIFF, $applyXLIFF ],
			[ $isDelivered, Fns::always( Either::of( 'applied-without-changes'  ) ) ],
			[ $userClickedCompleteInATE, Fns::always( Either::of( 'underway' ) ) ],
			[ $isTranslating, Fns::always( Either::of( 'in-progress' ) ) ],
			[ Logic::isEmpty(), Fns::always( Either::left( 'error' ) ) ],
			[ $otherwise, Fns::always( Either::of( 'in-progress' ) ) ]
		] );

		return Either::of( $jobId )
		             ->map( [ $ateAPI, 'checkJobStatus' ] )
		             ->chain( $handleATEResult );
	}

	private function completeWithoutChanges( $jobId ) {
		$applyWithoutChanges = pipe(
			Fns::tap( Jobs::setStatus( Fns::__, ICL_TM_COMPLETE ) ),
			Fns::tap( Jobs::setReviewStatus( Fns::__, ReviewStatus::NEEDS_REVIEW ) )
		);

		return Either::of( $jobId )
		             ->map( $applyWithoutChanges )
		             ->map( Fns::always( 'applied-without-changes' ) );
	}
}
