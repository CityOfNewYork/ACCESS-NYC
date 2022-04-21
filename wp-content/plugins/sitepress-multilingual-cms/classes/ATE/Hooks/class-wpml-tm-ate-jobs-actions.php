<?php

use WPML\FP\Fns;
use WPML\FP\Json;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\FP\Relation;
use WPML\TM\API\Jobs;
use WPML\FP\Wrapper;
use WPML\Settings\PostType\Automatic;
use WPML\Setup\Option;
use WPML\TM\ATE\JobRecords;
use WPML\TM\ATE\Log\Storage;
use WPML\TM\ATE\Log\Entry;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;
use WPML\TM\API\ATE\LanguageMappings;
use WPML\Element\API\Languages;

/**
 * @author OnTheGo Systems
 */
class WPML_TM_ATE_Jobs_Actions implements IWPML_Action {
	const RESPONSE_ATE_NOT_ACTIVE_ERROR     = 403;
	const RESPONSE_ATE_DUPLICATED_SOURCE_ID = 417;
	const RESPONSE_ATE_UNEXPECTED_ERROR     = 500;

	const RESPONSE_ATE_ERROR_NOTICE_ID    = 'ate-update-error';
	const RESPONSE_ATE_ERROR_NOTICE_GROUP = 'default';

	const CREATE_ATE_JOB_CHUNK_WORDS_LIMIT = 2000;

	/**
	 * @var WPML_TM_ATE_API
	 */
	private $ate_api;
	/**
	 * @var WPML_TM_ATE_Jobs
	 */
	private $ate_jobs;

	/**
	 * @var WPML_TM_AMS_Translator_Activation_Records
	 */
	private $translator_activation_records;

	/** @var bool */
	private $is_second_attempt_to_get_jobs_data = false;
	/**
	 * @var SitePress
	 */
	private $sitepress;
	/**
	 * @var WPML_Current_Screen
	 */
	private $current_screen;

	/**
	 * WPML_TM_ATE_Jobs_Actions constructor.
	 *
	 * @param \WPML_TM_ATE_API                           $ate_api
	 * @param \WPML_TM_ATE_Jobs                          $ate_jobs
	 * @param \SitePress                                 $sitepress
	 * @param \WPML_Current_Screen                       $current_screen
	 * @param \WPML_TM_AMS_Translator_Activation_Records $translator_activation_records
	 */
	public function __construct(
		WPML_TM_ATE_API $ate_api,
		WPML_TM_ATE_Jobs $ate_jobs,
		SitePress $sitepress,
		WPML_Current_Screen $current_screen,
		WPML_TM_AMS_Translator_Activation_Records $translator_activation_records

	) {
		$this->ate_api                       = $ate_api;
		$this->ate_jobs                      = $ate_jobs;
		$this->sitepress                     = $sitepress;
		$this->current_screen                = $current_screen;
		$this->translator_activation_records = $translator_activation_records;
	}

	public function add_hooks() {
		add_action( 'wpml_added_translation_job', [ $this, 'added_translation_job' ], 10, 2 );
		add_action( 'wpml_added_translation_jobs', [ $this, 'added_translation_jobs' ], 10, 2 );
		add_action( 'admin_notices', [ $this, 'handle_messages' ] );

		add_filter( 'wpml_tm_ate_jobs_data', [ $this, 'get_ate_jobs_data_filter' ], 10, 2 );
		add_filter( 'wpml_tm_ate_jobs_editor_url', [ $this, 'get_editor_url' ], 10, 3 );
	}

	public function handle_messages() {
		if ( $this->current_screen->id_ends_with( WPML_TM_FOLDER . '/menu/translations-queue' ) ) {

			if ( array_key_exists( 'message', $_GET ) ) {
				if ( array_key_exists( 'ate_job_id', $_GET ) ) {
					$ate_job_id = filter_var( $_GET['ate_job_id'], FILTER_SANITIZE_NUMBER_INT );

					$this->resign_job_on_error( $ate_job_id );
				}
				$message = filter_var( $_GET['message'], FILTER_SANITIZE_STRING );
				?>

				<div class="error notice-error notice otgs-notice">
					<p><?php echo $message; ?></p>
				</div>

				<?php
			}
		}
	}

	/**
	 * @param int    $job_id
	 * @param string $translation_service
	 *
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function added_translation_job( $job_id, $translation_service ) {
		$this->added_translation_jobs( array( $translation_service => array( $job_id ) ) );
	}

	/**
	 * @param array $jobs
     * @param int|null $sentFrom
	 *
	 * @return bool|void
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function added_translation_jobs( array $jobs, $sentFrom = null ) {
		$oldEditor = wpml_tm_load_old_jobs_editor();
		$job_ids   = Fns::reject( [ $oldEditor, 'shouldStickToWPMLEditor' ], Obj::propOr( [], 'local', $jobs ) );

		if ( ! $job_ids ) {
			return;
		}

		$jobs = Fns::map( 'wpml_tm_create_ATE_job_creation_model', $job_ids );

		$responses = Fns::map(
			Fns::unary( partialRight( [ $this, 'create_jobs' ], $sentFrom ) ),
			$this->getChunkedJobs( $jobs )
		);
		$created_jobs = $this->getResponsesJobs( $responses, $jobs );

		if ( $created_jobs ) {

			$created_jobs = $this->map_response_jobs( $created_jobs );

			$this->ate_jobs->warm_cache( array_keys( $created_jobs ) );

			foreach ( $created_jobs as $wpml_job_id => $ate_job_id ) {
				$this->ate_jobs->store( $wpml_job_id, [ JobRecords::FIELD_ATE_JOB_ID => $ate_job_id ] );
				$oldEditor->set( $wpml_job_id, WPML_TM_Editors::ATE );
				$translationJob = wpml_tm_load_job_factory()->get_translation_job( $wpml_job_id, false, 0, true );
				$jobType        = $this->getJobType( $translationJob );
				wpml_tm_load_job_factory()->update_job_data(
					$wpml_job_id,
					[ 'automatic' => $jobType === 'auto' ? 1 : 0 ]
				);

				if ( $sentFrom === Jobs::SENT_RETRY ) {
					Jobs::setStatus( $wpml_job_id, ICL_TM_WAITING_FOR_TRANSLATOR );
				}
			}

			$message = __( '%1$s jobs added to the Advanced Translation Editor.', 'wpml-translation-management' );
			$this->add_message( 'updated', sprintf( $message, count( $created_jobs ) ), 'wpml_tm_ate_create_job' );
		} else {
			if ( Lst::includes( $sentFrom, [ Jobs::SENT_AUTOMATICALLY, Jobs::SENT_RETRY ] ) ) {
				if ( $sentFrom === Jobs::SENT_RETRY ) {
					$updateJob = function ($jobId) {
						Jobs::incrementRetryCount($jobId);
						$this->logRetryError( $jobId );
					};
				} else {
					$updateJob = function ( $jobId ) use ( $oldEditor ) {
						$this->logError( $jobId );

						$translationJob = wpml_tm_load_job_factory()->get_translation_job( $jobId, false, 0, true );
						$jobType        = $this->getJobType( $translationJob );
						if ( $jobType === 'auto' ) {
							Jobs::setStatus( $jobId, ICL_TM_ATE_NEEDS_RETRY );
							$oldEditor->set( $jobId, WPML_TM_Editors::ATE );
							wpml_tm_load_job_factory()->update_job_data( $jobId, [ 'automatic' => 1 ] );
						}
					};
				}

				wpml_collect( $job_ids )->map( $updateJob );
			}

			$this->add_message(
				'error',
				__(
					'Jobs could not be created in Advanced Translation Editor. Please try again or contact the WPML support for help.',
					'wpml-translation-management'
				),
				'wpml_tm_ate_create_job'
			);
		}
	}

	private function map_response_jobs( $responseJobs ) {
		$result = [];
		foreach ( $responseJobs as $rid => $ate_job_id ) {
			$jobId = \WPML\TM\API\Job\Map::fromRid( $rid );
			if ( $jobId ) {
				$result[ $jobId ] = $ate_job_id;
			}
		}

		return $result;
	}

	/**
	 * @param string      $type
	 * @param string      $message
	 * @param string|null $id
	 */
	private function add_message( $type, $message, $id = null ) {
		do_action( 'wpml_tm_basket_add_message', $type, $message, $id );
	}

	/**
	 * @param array    $jobsData
     * @param int|null $sentFrom
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function create_jobs( array $jobsData, $sentFrom  ) {
		$setJobType = Logic::ifElse( Fns::always( $sentFrom ), Obj::assoc( 'job_type', $sentFrom ), Fns::identity() );

		list( $existing, $new ) = Lst::partition(
			pipe( Obj::propOr( null, 'existing_ate_id' ), Logic::isNotNull() ),
			$jobsData['jobs']
		);

		$isAuto = Relation::propEq( 'type', 'auto', $jobsData );

		return Wrapper::of( [ 'jobs' => $new, 'existing_jobs' => Lst::pluck( 'existing_ate_id', $existing ) ] )
		              ->map( Obj::assoc( 'auto_translate', $isAuto && Option::shouldTranslateEverything() ) )
		              ->map( Obj::assoc( 'preview', $isAuto && Option::shouldBeReviewed() ) )
		              ->map( $setJobType )
		              ->map( 'wp_json_encode' )
		              ->map( Json::toArray() )
		              ->map( [ $this->ate_api, 'create_jobs' ] )
		              ->get();
	}

	/**
	 * After implementation of wpmltm-3211 and wpmltm-3391, we should not find missing ATE IDs anymore.
	 * Some code below seems dead but we'll keep it for now in case we are missing a specific context.
	 *
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-3211
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wpmltm-3391
	 */
	private function get_ate_jobs_data( array $translation_jobs ) {
		$ate_jobs_data      = array();
		$skip_getting_data  = false;
		$ate_jobs_to_create = array();

		$this->ate_jobs->warm_cache( wpml_collect( $translation_jobs )->pluck( 'job_id' )->toArray() );

		foreach ( $translation_jobs as $translation_job ) {
			if ( $this->is_ate_translation_job( $translation_job ) ) {
				$ate_job_id = $this->get_ate_job_id( $translation_job->job_id );
				// Start of possibly dead code.
				if ( ! $ate_job_id ) {
					$ate_jobs_to_create[] = $translation_job->job_id;
					$skip_getting_data    = true;
				}
				// End of possibly dead code.

				if ( ! $skip_getting_data ) {
					$ate_jobs_data[ $translation_job->job_id ] = [ 'ate_job_id' => $ate_job_id ];
				}
			}
		}

		// Start of possibly dead code.
		if (
			! $this->is_second_attempt_to_get_jobs_data &&
			$ate_jobs_to_create &&
			$this->added_translation_jobs( array( 'local' => $ate_jobs_to_create ) )
		) {
			$ate_jobs_data                            = $this->get_ate_jobs_data( $translation_jobs );
			$this->is_second_attempt_to_get_jobs_data = true;
		}
		// End of possibly dead code.

		return $ate_jobs_data;
	}

	/**
	 * @param string      $default_url
	 * @param int         $job_id
	 * @param null|string $return_url
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function get_editor_url( $default_url, $job_id, $return_url = null ) {
		if ( $this->translator_activation_records->is_current_user_activated() ) {
			$ate_job_id = $this->ate_jobs->get_ate_job_id( $job_id );
			if ( $ate_job_id ) {
				if ( ! $return_url ) {
					$return_url = add_query_arg(
						array(
							'page'           => WPML_TM_FOLDER . '/menu/translations-queue.php',
							'ate-return-job' => $job_id,
						),
						admin_url( '/admin.php' )
					);
				}
				$ate_job_url = $this->ate_api->get_editor_url( $ate_job_id, $return_url );
				if ( $ate_job_url && ! is_wp_error( $ate_job_url ) ) {
					return $ate_job_url;
				}
			}
		}

		return $default_url;
	}

	/**
	 * @param $ignore
	 * @param array  $translation_jobs
	 *
	 * @return array
	 */
	public function get_ate_jobs_data_filter( $ignore, array $translation_jobs ) {
		return $this->get_ate_jobs_data( $translation_jobs );
	}

	private function get_ate_job_id( $job_id ) {
		return $this->ate_jobs->get_ate_job_id( $job_id );
	}

	/**
	 * @param mixed $response
	 *
	 * @throws \RuntimeException
	 */
	protected function check_response_error( $response ) {
		if ( is_wp_error( $response ) ) {
			$code    = 0;
			$message = $response->get_error_message();
			if ( $response->error_data && is_array( $response->error_data ) ) {
				foreach ( $response->error_data as $http_code => $error_data ) {
					$code    = $error_data[0]['status'];
					$message = '';

					switch ( (int) $code ) {
						case self::RESPONSE_ATE_NOT_ACTIVE_ERROR:
							$wp_admin_url  = admin_url( 'admin.php' );
							$mcsetup_page  = add_query_arg(
								array(
									'page' => WPML_TM_FOLDER . WPML_Translation_Management::PAGE_SLUG_SETTINGS,
									'sm'   => 'mcsetup',
								),
								$wp_admin_url
							);
							$mcsetup_page .= '#ml-content-setup-sec-1';

							$resend_link = '<a href="' . $mcsetup_page . '">'
										   . esc_html__( 'Resend that email', 'wpml-translation-management' )
										   . '</a>';
							$message    .= '<p>'
											. esc_html__( 'WPML cannot send these documents to translation because the Advanced Translation Editor is not fully set-up yet.', 'wpml-translation-management' )
											. '</p><p>'
											. esc_html__( 'Please open the confirmation email that you received and click on the link inside it to confirm your email.', 'wpml-translation-management' )
											. '</p><p>'
											. $resend_link
											. '</p>';
							break;
						case self::RESPONSE_ATE_DUPLICATED_SOURCE_ID:
						case self::RESPONSE_ATE_UNEXPECTED_ERROR:
						default:
							$message = '<p>'
									   . __( 'Advanced Translation Editor error:', 'wpml-translation-management' )
									   . '</p><p>'
									   . $error_data[0]['message']
									   . '</p>';
					}

					$message = '<p>' . $message . '</p>';
				}
			}
			/** @var WP_Error $response */
			throw new RuntimeException( $message, $code );
		}
	}

	/**
	 * @param $ate_job_id
	 */
	private function resign_job_on_error( $ate_job_id ) {
		$job_id = $this->ate_jobs->get_wpml_job_id( $ate_job_id );
		if ( $job_id ) {
			wpml_load_core_tm()->resign_translator( $job_id );
		}
	}

	/**
	 * @param $translation_job
	 *
	 * @return bool
	 */
	private function is_ate_translation_job( $translation_job ) {
		return 'local' === $translation_job->translation_service
			   && WPML_TM_Editors::ATE === $translation_job->editor;
	}

	/**
	 * @param array $responses
	 * @param \WPML_TM_ATE_Models_Job_Create[] $sentJobs
	 *
	 * @return array
	 */
	private function getResponsesJobs( $responses, $sentJobs ) {
		$jobs = [];

		foreach ( $responses as $response ) {
			try {
				$this->check_response_error( $response );

				if ( $response && isset( $response->jobs ) ) {
					$jobs = $jobs + (array) $response->jobs;
				}
			} catch ( RuntimeException $ex ) {
				do_action( 'wpml_tm_basket_add_message', 'error', $ex->getMessage() );
			}
		}

		$existingJobs = wpml_collect( $sentJobs )
			->filter( Obj::prop( 'existing_ate_id' ) )
			->map( Obj::pick( [ 'source_id', 'existing_ate_id' ] ) )
			->keyBy( 'source_id' )
			->map( Obj::prop( 'existing_ate_id' ) )
			->toArray();

		return $jobs + $existingJobs;
	}

	/**
	 * @param \WPML_TM_ATE_Models_Job_Create[] $jobs
	 *
	 * @return array
	 */
	private function getChunkedJobs( $jobs ) {
		$chunkedJobs      = [];
		$currentChunk     = -1;
		$currentWordCount = 0;
		$chunkType = 'auto';

		$newChunk = function( $chunkType ) use ( &$chunkedJobs, &$currentChunk, &$currentWordCount ) {
			$currentChunk ++;
			$currentWordCount             = 0;
			$chunkedJobs[ $currentChunk ] = [ 'type' => $chunkType, 'jobs' => [] ];
		};

		$newChunk( $chunkType );

		foreach ( $jobs as $job ) {
			/** @var WPML_Element_Translation_Job $translationJob */
			$translationJob = wpml_tm_load_job_factory()->get_translation_job( $job->source_id, false, 0, true );
			if ( $translationJob ) {

				if ( ! Obj::prop( 'existing_ate_id', $job ) ) {
					$currentWordCount += $translationJob->estimate_word_count();
				}

				$jobType = $this->getJobType( $translationJob );
				if ( $jobType !== $chunkType ) {
					$chunkType = $jobType;
					$newChunk( $chunkType );
				}
				if ( $currentWordCount > self::CREATE_ATE_JOB_CHUNK_WORDS_LIMIT && count( $chunkedJobs[ $currentChunk ] ) > 0 ) {
					$newChunk( $chunkType );
				}
			}

			$chunkedJobs[ $currentChunk ]['jobs'] [] = $job;

		}

		$hasJobs = pipe( Obj::prop( 'jobs' ), Lst::length() );

		return Fns::filter( $hasJobs, $chunkedJobs );
	}

	/**
	 * @param int $jobId
	 */
	private function logRetryError( $jobId ) {
		$job = Jobs::get( $jobId );
		if ( $job && $job->ate_comm_retry_count ) {
			Storage::add( Entry::retryJob( $jobId,
				[
					'retry_count' => $job->ate_comm_retry_count
				]
			) );
		}
	}

	/**
	 * @param int $jobId
	 */
	private function logError( $jobId ) {
		$job = Jobs::get( $jobId );
		if ( $job ) {
			Storage::add( Entry::retryJob( $jobId, [
					'retry_count' => 0,
					'comment'     => 'Sending job to ate failed, queued to be sent again.',
				]
			) );
		}
	}

	private function getJobType( $translationJob ) {
		$document = $translationJob->get_original_document();
		if ( ! $document || $document instanceof WPML_Package ) {
			return 'manual';
		} else {
			return $translationJob->get_source_language_code() === Languages::getDefaultCode() &&
                   Jobs::isEligibleForAutomaticTranslations( $translationJob->get_id() ) ? 'auto' : 'manual';
		}
	}
}
