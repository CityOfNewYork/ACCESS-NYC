<?php

namespace WPML\TM\ATE\Review;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Post;
use WPML\TM\API\Job\Map;
use WPML\TM\API\Jobs;
use function WPML\FP\pipe;

class Cancel implements IHandler {

	public function run( Collection $data ) {
		$jobIds       = $data->get( 'jobsIds' );
		$deleteDrafts = $data->get( 'deleteDrafts' );

		$reviewJobs = wpml_collect( $jobIds )
			->map( Jobs::get() )
			->filter( ReviewStatus::doesJobNeedReview() );

		if ( $reviewJobs->count() ) {
			$reviewJobs->map( Obj::prop( 'job_id' ) )
			           ->map( Jobs::clearReviewStatus() );

			if ( $deleteDrafts ) {
				$this->deleteDrafts( $reviewJobs );
			}

			return $reviewJobs->pluck( 'job_id' )->map( Cast::toInt() );
		}

		return [];
	}

	private function deleteDrafts( Collection $reviewJobs ) {
		$doCancelJobsAction = function ( Collection $jobIds ) {
			$getJobEntity = function ( $jobId ) {
				return wpml_tm_get_jobs_repository()->get_job( Map::fromJobId( $jobId ), \WPML_TM_Job_Entity::POST_TYPE );
			};

			$jobEntities = $jobIds->map( $getJobEntity )->toArray();
			do_action( 'wpml_tm_jobs_cancelled', $jobEntities );
		};

		$getTranslatedId = Fns::memorize( pipe( Jobs::get(), Jobs::getTranslatedPostId() ) );

		$isDraft = pipe( $getTranslatedId, Post::getStatus(), Relation::equals( 'draft' ) );

		$deleteTranslatedPost = pipe( $getTranslatedId, Post::delete() );

		$reviewJobs = $reviewJobs->map( Obj::prop( 'job_id' ) )
		                         ->map( Jobs::setNotTranslatedStatus() )
		                         ->map( Jobs::clearTranslated() );

		$doCancelJobsAction( $reviewJobs );

		$reviewJobs->filter( $isDraft )
		           ->map( Fns::tap( $deleteTranslatedPost ) )
		           ->map( Jobs::delete() );
	}
}
