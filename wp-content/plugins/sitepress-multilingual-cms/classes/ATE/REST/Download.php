<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\TM\ATE\REST;

use WP_REST_Request;
use WPML\Collect\Support\Collection;
use WPML\Element\API\PostTranslations;
use WPML\FP\Cast;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Download\Process;
use WPML\TM\ATE\Review\PreviewLink;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\TM\ATE\Review\StatusIcons;
use WPML\TM\ATE\SyncLock;
use WPML\TM\REST\Base;
use WPML_TM_ATE_AMS_Endpoints;
use function WPML\Container\make;
use function WPML\FP\pipe;

class Download extends Base {
	/**
	 * @return array
	 */
	public function get_routes() {
		return [
			[
				'route' => WPML_TM_ATE_AMS_Endpoints::DOWNLOAD_JOBS,
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'download' ],
				],
			],
		];
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return [
			'manage_options',
			'manage_translations',
			'translate',
		];
	}

	public function download( WP_REST_Request $request ) {
		$lock = make( SyncLock::class );
		if ( ! $lock->create( $request->get_param( 'lockKey' ) ) ) {
			return [];
		}

		$jobs = make( Process::class )->run( $request->get_param( 'jobs' ) );

		return $this->getJobs( $jobs, $request->get_param( 'returnUrl' ) )->all();
	}

	/**
	 * @param Collection $processedJobs
	 * @param string $returnUrl
	 *
	 * @return Collection
	 */
	public static function getJobs( Collection $processedJobs, $returnUrl ) {
		$getLink = Logic::ifElse(
			ReviewStatus::doesJobNeedReview(),
			Fns::converge( PreviewLink::getWithSpecifiedReturnUrl( $returnUrl ), [ Obj::prop( 'translatedPostId' ), Obj::prop( 'jobId' ) ] ),
			pipe( Obj::prop( 'jobId' ), Jobs::getEditUrl( $returnUrl ) )
		);

		$getLabel = Logic::ifElse(
			ReviewStatus::doesJobNeedReview(),
			StatusIcons::getReviewTitle( 'language_code' ),
			StatusIcons::getEditTitle( 'language_code' )
		);

		return $processedJobs->pluck( 'jobId' )
		                     ->map( Jobs::get() )
		                     ->map( Obj::addProp( 'translatedPostId', Jobs::getTranslatedPostId() ) )
		                     ->map( Obj::renameProp( 'job_id', 'jobId' ) )
		                     ->map( Obj::renameProp( 'editor_job_id', 'ateJobId' ) )
		                     ->map( Obj::addProp( 'viewLink', $getLink ) )
		                     ->map( Obj::addProp( 'label', $getLabel ) )
		                     ->map( Obj::pick( [ 'jobId', 'viewLink', 'automatic', 'status', 'label', 'review_status', 'ateJobId' ] ) )
		                     ->map( Obj::evolve( [
			                     'jobId'     => Cast::toInt(),
			                     'automatic' => Cast::toInt(),
			                     'status'    => Cast::toInt(),
			                     'ateJobId'  => Cast::toInt(),
		                     ] ) );
	}
}
