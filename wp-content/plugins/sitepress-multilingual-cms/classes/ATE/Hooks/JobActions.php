<?php

namespace WPML\TM\ATE\Hooks;

use function WPML\Container\make;
use WPML\Element\API\Languages;
use WPML\FP\Fns;
use function WPML\FP\invoke;
use WPML\FP\Lst;
use WPML\FP\Obj;
use function WPML\FP\pipe;
use WPML\FP\Relation;
use WPML\Setup\Option;

class JobActions implements \IWPML_Action {

	/** @var \WPML_TM_ATE_API $apiClient */
	private $apiClient;

	public function __construct( \WPML_TM_ATE_API $apiClient ) {
		$this->apiClient = $apiClient;
	}

	public function add_hooks() {
		add_action( 'wpml_tm_job_cancelled', [ $this, 'cancelJobInATE' ] );
		add_action( 'wpml_tm_jobs_cancelled', [ $this, 'cancelJobsInATE' ] );
		add_action( 'wpml_set_translate_everything', [ $this, 'hideJobsAfterTranslationMethodChange' ] );
		add_action( 'wpml_update_active_languages', [ $this, 'hideJobsAfterRemoveLanguage' ] );
	}

	public function cancelJobInATE( \WPML_TM_Post_Job_Entity $job ) {
		if ( $job->is_ate_editor() ) {
			$this->apiClient->cancelJobs( $job->get_editor_job_id() );
		}
	}

	public function cancelJobsInATE( array $jobs ) {

		$getIds = pipe(
			Fns::filter( invoke( 'is_ate_editor' ) ),
			Fns::map( invoke( 'get_editor_job_id' ) )
		);
		$this->apiClient->cancelJobs( $getIds( $jobs ) );
	}

	public function hideJobsAfterRemoveLanguage( $oldLanguages ) {
		$removedLanguages = Lst::diff( array_keys( $oldLanguages ), array_keys( Languages::getActive() ) );

		if ( $removedLanguages ) {
			$inProgressJobsSearchParams = self::getInProgressSearch()
			                                  ->set_target_language( array_values( $removedLanguages ) );

			$this->hideJobs( $inProgressJobsSearchParams );

			Fns::map( [ Option::class, 'removeLanguageFromCompleted' ], $removedLanguages );
		}
	}

	public function hideJobsAfterTranslationMethodChange( $translateEverythingActive ) {
		if ( ! $translateEverythingActive ) {
			$this->hideJobs( self::getInProgressSearch() );
		}
	}

	private static function getInProgressSearch() {
		return ( new \WPML_TM_Jobs_Search_Params() )->set_status( [
			ICL_TM_WAITING_FOR_TRANSLATOR,
			ICL_TM_IN_PROGRESS
		] );
	}

	private function hideJobs( \WPML_TM_Jobs_Search_Params $jobsSearchParams ) {
		$translationJobs = wpml_collect( wpml_tm_get_jobs_repository()->get( $jobsSearchParams ) )
			->filter( invoke( 'is_ate_editor' ) )
			->filter( invoke( 'is_automatic' ) );

		$canceledInATE = $this->apiClient->hideJobs(
			$translationJobs->map( invoke( 'get_editor_job_id' ) )->toArray()
		);

		if ( $canceledInATE && ! is_wp_error( $canceledInATE ) ) {
			$translationJobs = $translationJobs->filter( pipe(
				invoke( 'get_editor_job_id' ),
				Lst::includes( Fns::__, Obj::propOr( [], 'jobs', $canceledInATE ) )
			) );
		}

		$translationJobs->map( Fns::tap( invoke( 'set_status' )->with( ICL_TM_ATE_CANCELLED ) ) )
		                ->map( Fns::tap( [ make( \WPML_TP_Sync_Update_Job::class ), 'update_state' ] ) );
	}
}
