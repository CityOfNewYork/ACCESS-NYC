<?php

namespace WPML\TM\ATE\Review;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Post;
use WPML\TM\API\Job\Map;
use WPML\TM\API\Jobs;
use function WPML\FP\pipe;

class Cancel {

	public static function run( array $jobIds ) {
		$reviewJobs = wpml_collect( $jobIds )
			->map( Jobs::get() )
			->filter( ReviewStatus::doesJobNeedReview() );

		if ( $reviewJobs->count() ) {
			$reviewJobs->map( Obj::prop( 'job_id' ) )
			           ->map( Jobs::clearReviewStatus() );

			if ( Relation::propEq( 'delete-drafts', "1", $_POST ) ) {
				$doCancelJobAction = function ( $jobId ) {
					$jobEntity = wpml_tm_get_jobs_repository()->get_job( Map::fromJobId( $jobId ), \WPML_TM_Job_Entity::POST_TYPE );
					do_action( 'wpml_tm_job_cancelled', $jobEntity );

					return $jobId;
				};

				$getTranslatedId = Fns::memorize( pipe( Jobs::get(), Jobs::getTranslatedPostId() ) );

				$isDraft = pipe( $getTranslatedId, Post::getStatus(), Relation::equals( 'draft' ) );

				$deleteTranslatedPost = pipe( $getTranslatedId, Post::delete() );

				$reviewJobs->map( Obj::prop( 'job_id' ) )
				           ->map( Jobs::setNotTranslatedStatus() )
				           ->map( Jobs::clearTranslated() )
				           ->map( $doCancelJobAction )
				           ->filter( $isDraft )
				           ->map( Fns::tap( $deleteTranslatedPost ) )
				           ->map( Jobs::delete() );
			}
		}

	}
}
