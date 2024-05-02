<?php

namespace WPML\TM\Editor;

use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Left;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Right;
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
		$shouldOpenCTE = function ( $jobObject ) use ( $params ) {
			if ( ! \WPML_TM_ATE_Status::is_enabled() ) {
				return true;
			}

			if ( $this->isNewJobCreated( $params, $jobObject ) ) {
				return wpml_tm_load_old_jobs_editor()->shouldStickToWPMLEditor( $jobObject->get_id() );
			}

			return wpml_tm_load_old_jobs_editor()->editorForTranslationsPreviouslyCreatedUsingCTE() === \WPML_TM_Editors::WPML &&
			       wpml_tm_load_old_jobs_editor()->get_current_editor( $jobObject->get_id() ) === \WPML_TM_Editors::WPML;
		};

		/**
		 * It maybe needed when a job was translated via the Translation Proxy before and now, we want to open it in the editor.
		 *
		 * @param \WPML_Element_Translation_Job $jobObject
		 *
		 * @return \WPML_Element_Translation_Job
		 */
		$maybeUpdateTranslationServiceColumn = function ( $jobObject ) {
			if ( $jobObject->get_translation_service() !== 'local' ) {
				$jobObject->set_basic_data_property( 'translation_service', 'local' );
				Jobs::setTranslationService( $jobObject->get_id(), 'local' );
			}

			return $jobObject;
		};

		$dataOfTranslationCreatedInNativeEditorViaConnection = $this->manualJobs->maybeGetDataIfTranslationCreatedInNativeEditorViaConnection( $params );
		if ( $dataOfTranslationCreatedInNativeEditorViaConnection ) {
			update_post_meta( $dataOfTranslationCreatedInNativeEditorViaConnection['originalPostId'], \WPML_TM_Post_Edit_TM_Editor_Mode::POST_META_KEY_USE_NATIVE, 'yes' );

			return $this->displayWPNative( $dataOfTranslationCreatedInNativeEditorViaConnection );
		}

		return Either::of( $params )
		             ->map( [ $this->manualJobs, 'createOrReuse' ] )
		             ->filter( Logic::isTruthy() )
		             ->filter( invoke( 'user_can_translate' )->with( User::getCurrent() ) )
		             ->map( $maybeUpdateTranslationServiceColumn )
		             ->map( Logic::ifElse( $shouldOpenCTE, $this->displayCTE(), $this->tryToDisplayATE( $params ) ) )
		             ->getOrElse( [ 'editor' => \WPML_TM_Editors::NONE, 'jobObject' => null ] );
	}

	/**
	 * @param array                         $params
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

			/**
			 * Create a new ATE job when somebody clicks the "pencil" icon to edit existing translation.
			 *
			 * @param \WPML_Element_Translation_Job $jobObject
			 *
			 * @return Either<\WPML_Element_Translation_Job>
			 */
			$cloneCompletedATEJob = function ( $jobObject ) use ( $params ) {
				if ( $this->isValidATEJob( $jobObject ) && (int) $jobObject->get_status_value() === ICL_TM_COMPLETE ) {
					$sentFrom = isset( $params['preview'] ) ? Jobs::SENT_FROM_REVIEW : Jobs::SENT_MANUALLY;

					return $this->clone_jobs->cloneCompletedATEJob( $jobObject, $sentFrom )
					                        ->bimap( $this->handleATEJobCreationError( $params, self::ATE_JOB_COULD_NOT_BE_CREATED ), Fns::identity() );
				}

				return Either::of( $jobObject );
			};

			$handleMissingATEJob = function ( $jobObject ) use ( $params ) {
				// ATE editor is already set. All fine, we can proceed.
				if ( $this->isValidATEJob( $jobObject ) ) {
					return Either::of( $jobObject );
				}

				/**
				 * The new job has been created because either there was no translation at all or translation was "needs update".
				 * The ATE job could not be created inside WPML_TM_ATE_Jobs_Actions::added_translation_jobs ,and we have to return the error message.
				 */
				if ( $this->isNewJobCreated( $params, $jobObject ) ) {
					return Either::left( $this->handleATEJobCreationError( $params, self::ATE_JOB_COULD_NOT_BE_CREATED, $jobObject ) );
				}

				/**
				 *  It creates a corresponding job in ATE for already existing WPML job in such situations:
				 *  1. Previously job was created in CTE, but a user selected the setting to translate existing CTE jobs in ATE
				 *  2. The job used to be handled by the Translation Proxy or the native WP editor
				 *  3. ATE job could not be created before and user clicked "Retry" button
				 *  4. Job was sent via basket and ATE job could not be created
				 */
				return $this->createATECounterpartForExistingWPMLJob( $params, $jobObject );
			};

			return Either::of( $jobObject )
			             ->chain( $handleNotActiveATE )
			             ->chain( $cloneCompletedATEJob )
			             ->chain( $handleMissingATEJob )
			             ->map( Fns::tap( pipe( invoke( 'get_id' ), Jobs::setStatus( Fns::__, ICL_TM_IN_PROGRESS ) ) ) )
			             ->map( $this->openATE( $params ) )
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
			wpml_tm_load_old_jobs_editor()->set( $jobObject->get_id(), \WPML_TM_Editors::WPML );

			return [ 'editor' => \WPML_TM_Editors::WPML, 'jobObject' => $jobObject ];
		} );

		return call_user_func_array( $fn, func_get_args() );
	}

	/**
	 * @param array $dataOfTranslationCreatedInNativeEditorViaConnection
	 *
	 * @return array
	 */
	private function displayWPNative( array $dataOfTranslationCreatedInNativeEditorViaConnection ) {
		$url = 'post.php?' . http_build_query(
				[
					'lang'      => $dataOfTranslationCreatedInNativeEditorViaConnection['targetLanguageCode'],
					'action'    => 'edit',
					'post_type' => str_replace( 'post_', '', $dataOfTranslationCreatedInNativeEditorViaConnection['postType'] ),
					'post'      => $dataOfTranslationCreatedInNativeEditorViaConnection['translatedPostId']
				]
			);


		return [ 'editor' => \WPML_TM_Editors::WP, 'jobObject' => null, 'url' => $url ];
	}

	/**
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return void
	 */
	private function maybeSetReviewStatus( $jobObject ) {
		if ( Relation::propEq( 'review_status', ReviewStatus::NEEDS_REVIEW, $jobObject->to_array() ) ) {
			Jobs::setReviewStatus( $jobObject->get_id(), SetupOption::shouldBeReviewed() ? ReviewStatus::EDITING : null );
		}
	}

	/**
	 * It returns an url to place where a user should be redirected. The url contains a job id and error's code.
	 *
	 * @param array                         $params
	 * @param int                           $code
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
	 * @param string                        $editor
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return bool
	 */
	private function isJobEditorEqualTo( $editor, $jobObject ) {
		return $jobObject->get_basic_data_property( 'editor' ) === $editor;
	}

	/**
	 * It checks if we created a new entry in wp_icl_translate_job table.
	 * It happens when none translation for a specific language has existed so far or when a translation has been "needs update".
	 *
	 * @param array                         $params
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return bool
	 */
	private function isNewJobCreated( $params , $jobObject ) {
		return (int) $jobObject->get_id() !== (int) Obj::prop( 'job_id', $params );
	}

	/**
	 * @param array                         $params
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return callable|Left<array>|Right<\WPML_Element_Translation_Job>
	 */
	private function createATECounterpartForExistingWPMLJob( $params, $jobObject ) {
		if ( $this->clone_jobs->cloneWPMLJob( $jobObject->get_id() ) ) {
			ATERetry::reset( $jobObject->get_id() );
			$jobObject->set_basic_data_property( 'editor', \WPML_TM_Editors::ATE );

			return Either::of( $jobObject );
		}

		return Either::left( $this->handleATEJobCreationError( $params, self::ATE_JOB_COULD_NOT_BE_CREATED, $jobObject ) );
	}

	/**
	 * At this stage, we know that a corresponding job in ATE is created and we should open ATE editor.
	 * We are trying to do that.
	 *
	 * @param array                         $params
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return false|mixed
	 */
	private function openATE( $params = null, $jobObject = null ) {
		$fn = curryN( 2, function ( $params, $jobObject ) {
			$this->maybeSetReviewStatus( $jobObject );

			$editor_url = apply_filters( 'wpml_tm_ate_jobs_editor_url', '', $jobObject->get_id(), $this->getReturnUrl( $params ) );

			if ( $editor_url ) {
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

			$return_url_parts = wp_parse_url( (string) $return_url );

			$admin_url       = get_admin_url();
			$admin_url_parts = wp_parse_url( $admin_url );

			if ( strpos( $return_url_parts['path'], $admin_url_parts['path'] ) === 0 ) {
				$admin_url_parts['path'] = $return_url_parts['path'];
			} else {
				$admin_url_parts = $return_url_parts;
			}

			$admin_url_parts['query'] = $this->prepareQueryParameters(
				Obj::propOr( '', 'query', $return_url_parts ),
				Obj::prop( 'lang', $params )
			);

			$return_url = http_build_url( $admin_url_parts );
		}

		return $return_url;
	}

	private function prepareQueryParameters( $query, $returnLanguage ) {
		$parameters = [];
		parse_str( $query, $parameters );

		unset( $parameters['ate_original_id'] );
		unset( $parameters['back'] );
		unset( $parameters['complete'] );

		if ( $returnLanguage ) {
			// We need the lang parameter to display the post list in the language which was used before ATE.
			$parameters['lang'] = $returnLanguage;
		}

		return http_build_query( $parameters );
	}

	/**
	 * @param \WPML_Element_Translation_Job $jobObject
	 *
	 * @return bool
	 */
	private function isValidATEJob( \WPML_Element_Translation_Job $jobObject ) {
		return $this->isJobEditorEqualTo( \WPML_TM_Editors::ATE, $jobObject ) &&
		       (int) $jobObject->get_basic_data_property( 'editor_job_id' ) > 0;
	}
}
