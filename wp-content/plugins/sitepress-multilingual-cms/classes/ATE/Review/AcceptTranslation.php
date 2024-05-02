<?php

namespace WPML\TM\ATE\Review;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\LIB\WP\Post;
use WPML\TM\API\Jobs;
use function WPML\FP\partial;
use function WPML\FP\pipe;

class AcceptTranslation implements IHandler {

	public function run( Collection $data ) {
		$postId  = $data->get( 'postId' );
		$jobId   = $data->get( 'jobId' );
		$canEdit = partial( 'current_user_can', 'edit_post' );

		$completeJob = Fns::tap(
			pipe(
				Fns::always( $jobId ),
				Fns::tap( Jobs::setStatus( Fns::__, ICL_TM_COMPLETE ) ),
				Fns::tap( Jobs::setReviewStatus( Fns::__, ReviewStatus::ACCEPTED ) )
			)
		);

		return Either::of( $postId )
		             ->filter( $canEdit )
		             ->map( Post::setStatusWithoutFilters( Fns::__, 'publish' ) )
		             ->map( $completeJob )
		             ->bimap( Fns::always( $jobId ), Fns::always( $jobId ) );
	}
}
