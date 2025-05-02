<?php

namespace WPML\TM\ATE\AutoTranslate\Endpoint;

use WPML\FP\Either;
use WPML\Collect\Support\Collection;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Review\PreviewLink;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\TM\ATE\Review\StatusIcons;
use function WPML\FP\pipe;

class GetJobsInfo implements \WPML\Ajax\IHandler {

	/**
	 * @param Collection<jobIds: int[], returnUrl: string> $data
	 *
	 * @return Either<{jobId: int, automatic:'1'|'0', status: int, ateJobId: int}[]>
	 */
	public function run( Collection $data ) {
		$jobIds    = $data->get( 'jobIds', [] );
		$returnUrl = $data->get( 'returnUrl', '' );

		$getLink = Logic::ifElse(
			ReviewStatus::doesJobNeedReview(),
			Fns::converge( PreviewLink::getWithSpecifiedReturnUrl( $returnUrl ), [
				Obj::prop( 'translatedPostId' ),
				Obj::prop( 'jobId' )
			] ),
			pipe( Obj::prop( 'jobId' ), Jobs::getEditUrl( $returnUrl ) )
		);

		$getLabel = Logic::ifElse(
			ReviewStatus::doesJobNeedReview(),
			StatusIcons::getReviewTitle( 'language_code' ),
			StatusIcons::getEditTitle( 'language_code' )
		);

		return Either::of( \wpml_collect( $jobIds )
			->map( Jobs::get() )
			->map( Obj::addProp( 'translatedPostId', Jobs::getTranslatedPostId() ) )
			->map( Obj::renameProp( 'job_id', 'jobId' ) )
			->map( Obj::renameProp( 'editor_job_id', 'ateJobId' ) )
			->map( Obj::addProp( 'viewLink', $getLink ) )
			->map( Obj::addProp( 'label', $getLabel ) )
			->map( Obj::pick( [
				'jobId',
				'viewLink',
				'automatic',
				'status',
				'label',
				'review_status',
				'ateJobId'
			] ) )
			->map( Obj::evolve( [
				'jobId'     => Cast::toInt(),
				'automatic' => Cast::toInt(),
				'status'    => Cast::toInt(),
				'ateJobId'  => Cast::toInt(),
			] ) ) );

	}

}