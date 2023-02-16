<?php

namespace WPML\TM\Editor;

use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\User;
use WPML\Setup\Option as SetupOption;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\Storage;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\TM\ATE\Sync\Trigger;
use WPML\TM\Jobs\Manual;
use WPML\TM\Menu\TranslationQueue\CloneJobs;
use function WPML\Container\make;
use function WPML\FP\curryN;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class Editor {

	const ATE_JOB_COULD_NOT_BE_CREATED = 101;
	const ATE_EDITOR_URL_COULD_NOT_BE_FETCHED = 102;
	const ATE_IS_NOT_ACTIVE = 103;

	/** @var CloneJobs */
	private $clone_jobs;

	/** @var Manual */
	private $manualJobs;

	/**
	 * Editor constructor.
	 *
	 * @param CloneJobs $clone_jobs
	 * @param Manual $manualJobs
	 */
	public function __construct( CloneJobs $clone_jobs, Manual $manualJobs ) {
		$this->clone_jobs = $clone_jobs;
		$this->manualJobs = $manualJobs;
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	public function open( $params ) {
		$isATEEnabled = [ \WPML_TM_ATE_Status::class, 'is_enabled' ];

		return Either::of( $params )
		             ->map( [ $this->manualJobs, 'createOrReuse' ] )
		             ->filter( Logic::isTruthy() )
		             ->filter( invoke( 'user_can_translate' )->with( User::getCurrent() ) )
		             ->map( Logic::ifElse( $isATEEnabled, $this->tryToDisplayATE( $params ), $this->displayCTE() ) )
		             ->getOrElse( [ 'editor' => \WPML_TM_Editors::NONE, 'jobObject' => null ] );
	}

	/**
	 * @param array $params
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return array
	 */
	private function tryToDisplayATE( $params = null, $jobObject = null ) {
		$fn = curryN( 2, function ( $params, $jobObject ) {
			$handleNotActiveATE = Logic::ifElse(
				[ \WPML_TM_ATE_Status::class, 'is_active' ],
				Either::of(),
				pipe( $this->handleATEJobCreationError( $params, self::ATE_IS_NOT_ACTIVE ), Either::left() )
			);

			return Either::of( $jobObject )
			             ->chain( $handleNotActiveATE )
			             ->chain( $this->maybeCreateATECounterpartForExistingWPMLJob( $params ) )
			             ->map( Logic::cond( [
				             [ $this->isEditor( \WPML_TM_Editors::ATE ), $this->openATE( $params ) ],
				             [ $this->shouldNewJobBeOpenedInATE( $params ), $this->handleATEJobCreationError( $params, self::ATE_JOB_COULD_NOT_BE_CREATED ) ],
				             [ $this->isEditor( \WPML_TM_Editors::WPML ), $this->displayCTE() ],
				             [ Fns::always( true ), $this->openATE( $params ) ],
			             ] ) )
			             ->coalesce( Fns::identity(), Fns::identity() )
			             ->get();
		} );

		return call_user_func_array( $fn, func_get_args() );
	}

	/**
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return array
	 */
	private function displayCTE( $jobObject = null ) {
		$fn = curryN( 1, function ( $jobObject ) {
			wpml_tm_load_old_jobs_editor()->set( $jobObject->get_id(), \WPML_TM_Editors::WPML ); // change it

			return [ 'editor' => \WPML_TM_Editors::WPML, 'jobObject' => $jobObject ];
		} );

		return call_user_func_array( $fn, func_get_args() );
	}


	private function maybeSetReviewStatus( $jobObject ) {
		if ( Relation::propEq( 'review_status', ReviewStatus::NEEDS_REVIEW, $jobObject->to_array() ) ) {
			Jobs::setReviewStatus( $jobObject->get_id(), SetupOption::shouldTranslateEverything() ? ReviewStatus::EDITING : null );
		}
	}

	/**
	 * It returns an url to place where a user should be redirected. The url contains a job id and error's code.
	 *
	 * @param array $params
	 * @param int $code
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return array
	 */
	private function handleATEJobCreationError( $params = null, $code = null, $jobObject = null ) {
		$fn = curryN( 3, function ( $params, $code, $jobObject ) {
			ATERetry::incrementCount( $jobObject->get_id() );

			$retryCount = ATERetry::getCount( $jobObject->get_id() );
			if ( $retryCount > 0 ) {
				Storage::add( Entry::retryJob( $jobObject->get_id(),
					[
						'retry_count' => ATERetry::getCount( $jobObject->get_id() )
					]
				) );
			}

			return [
				'editor' => \WPML_TM_Editors::ATE,
				'url'    => add_query_arg( [ 'ateJobCreationError' => $code, 'jobId' => $jobObject->get_id() ], $this->getReturnUrl( $params ) )
			];
		} );

		return call_user_func_array( $fn, func_get_args() );
	}

	/**
	 * It asserts a job's editor.
	 *
	 * @param string $editor
	 * @param \WPML_Element_Translation_Job  $jobObject
	 *
	 * @return bool
	 */
	private function isEditor( $editor = null, $jobObject = null ) {
		$isEditor = curryN( 2, function ( $editor, $jobObject ) {
			return $jobObject->get_basic_data_property( 'editor' ) === $editor;
		} );

		return call_user_func_array( $isEditor, func_get_args() );
	}

	/**
	 * It checks if we created a new entry in wp_icl_translate_job table.
	 * It happens when none for a specific lang existed so far or when a translation was "needs update".
	 *
	 * @param array $params
	 * @param \WPML_Element_Translation_Job  $jobObject
	 *
	 * @return bool
	 */
	private function isNewJobCreated( $params = null, $jobObject = null ) {
		$fn = curryN( 2, function ( $params, $jobObject ) {
			return (int) $jobObject->get_id() !== (int) Obj::prop( 'job_id', $params );
		} );

		return call_user_func_array( $fn, func_get_args() );
	}

	/**
	 * It creates a corresponding job in ATE for already existing WPML job in such situations:
	 *  1. Previously job was created in CTE, but a user selected the setting to translate existing CTE jobs in ATE
	 *  2. ATE job could not be created before and user clicked "Retry" button
	 *  3. Job was sent via basket and ATE job could not be created
	 *
	 * @param array $params
	 *
	 * @return callable :: \WPML_Element_Translation_Job->Either
	 */
	private function maybeCreateATECounterpartForExistingWPMLJob( array $params ) {
		$shouldUseATEForOldCTEJobs = function () {
			return wpml_tm_load_old_jobs_editor()->editorForTranslationsPreviouslyCreatedUsingCTE() === \WPML_TM_Editors::ATE;
		};

		$shouldCreateATECounterpartForExistingWPMLJob = function ( $jobObject ) use ( $params, $shouldUseATEForOldCTEJobs ) {
			return ! $this->isNewJobCreated( $params, $jobObject ) &&
			       ! $this->isEditor( \WPML_TM_Editors::ATE, $jobObject ) &&
			       (
				       $this->isEditor( \WPML_TM_Editors::NONE, $jobObject ) ||
				       $shouldUseATEForOldCTEJobs()
			       );
		};

		$createATECounterpartForExistingWPMLJob = function ( $jobObject ) use ( $params ) {
			$jobEditor = $this->clone_jobs->maybeCloneWPMLJob( $jobObject->get_id() ) ?
				\WPML_TM_Editors::ATE :
				$jobObject->get_basic_data_property( 'editor' );

			if ( $jobEditor === \WPML_TM_Editors::ATE ) {
				ATERetry::reset( $jobObject->get_id() );
				$jobObject->set_basic_data_property( 'editor', $jobEditor );

				return Either::of( $jobObject );
			}

			return Either::left( $this->handleATEJobCreationError( $params, self::ATE_JOB_COULD_NOT_BE_CREATED, $jobObject ) );
		};

		return Logic::ifElse(
			$shouldCreateATECounterpartForExistingWPMLJob,
			$createATECounterpartForExistingWPMLJob,
			Either::of()
		);
	}

	private function shouldNewJobBeOpenedInATE( array $params ) {
		return Logic::both(
			$this->isNewJobCreated( $params ),
			pipe( invoke( 'get_id' ), Logic::complement( [ wpml_tm_load_old_jobs_editor(), 'shouldStickToWPMLEditor' ] ) )
		);
	}

	/**
	 * At this stage, we know that a corresponding job in ATE is created and we should open ATE editor.
	 * We are trying to do that.
	 *
	 * @param array $params
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return false|mixed
	 */
	private function openATE( $params = null, $jobObject = null ) {
		$fn = curryN( 2, function ( $params, $jobObject ) {
			if ( \WPML_TM_Editors::ATE !== wpml_tm_load_old_jobs_editor()->get_current_editor( $jobObject->get_id() ) ) {
				wpml_tm_load_old_jobs_editor()->set( $jobObject->get_id(), \WPML_TM_Editors::ATE );
			}
			$sentFrom = isset( $params['preview'] ) ? Jobs::SENT_FROM_REVIEW : Jobs::SENT_MANUALLY;
			$this->clone_jobs->cloneCompletedATEJob( $jobObject, $sentFrom );
			$this->maybeSetReviewStatus( $jobObject );

			$editor_url = apply_filters( 'wpml_tm_ate_jobs_editor_url', '', $jobObject->get_id(), $this->getReturnUrl( $params ) );

			if ( $editor_url ) {
				make( Trigger::class )->setSyncRequiredForCurrentUser();

				$response['editor']    = \WPML_TM_Editors::ATE;
				$response['url']       = $editor_url;
				$response['jobObject'] = $jobObject;

				return $response;
			}

			return $this->handleATEJobCreationError( $params, self::ATE_EDITOR_URL_COULD_NOT_BE_FETCHED, $jobObject );
		} );

		return call_user_func_array( $fn, func_get_args() );
	}



	/**
	 * @return string
	 */
	private function getReturnUrl( $params ) {
		$return_url = '';

		if ( array_key_exists( 'return_url', $params ) ) {
			$return_url = filter_var( $params['return_url'], FILTER_SANITIZE_URL );

			$return_url_parts = wp_parse_url( $return_url );

			$admin_url       = get_admin_url();
			$admin_url_parts = wp_parse_url( $admin_url );

			if ( strpos( $return_url_parts['path'], $admin_url_parts['path'] ) === 0 ) {
				$admin_url_parts['path'] = $return_url_parts['path'];
			} else {
				$admin_url_parts = $return_url_parts;
			}

			if ( array_key_exists( 'query', $return_url_parts ) ) {
				$admin_url_parts['query'] = $this->filterQueryParameters( $return_url_parts['query'] );
			}

			$return_url = http_build_url( $admin_url_parts );
		}

		return $return_url;
	}

	private function filterQueryParameters( $query ) {
		$parameters = [];
		parse_str( $query, $parameters );

		unset( $parameters['ate_original_id'] );
		unset( $parameters['back'] );
		unset( $parameters['complete'] );

		return http_build_query( $parameters );
	}
}