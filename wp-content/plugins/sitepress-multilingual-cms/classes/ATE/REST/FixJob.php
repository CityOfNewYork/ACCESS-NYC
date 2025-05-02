<?php

namespace WPML\TM\ATE\REST;

use WPML\TM\ATE\ReturnedJobs;
use WP_REST_Request;
use WPML\Rest\Adaptor;
use WPML\TM\REST\Base;
use WPML_TM_ATE_AMS_Endpoints;
use \WPML_TM_ATE_API;
use \WPML_TM_ATE_Jobs;
use \WPML_TM_Jobs_Repository;
use WPML\FP\Obj;
use WPML\TM\ATE\API\RequestException;
use WPML\TM\ATE\Log\Entry;
use WPML\TM\ATE\Log\EventsTypes;

class FixJob extends Base {

	/**
	 * @var WPML_TM_ATE_Jobs
	 */
	private $ateJobs;

	/**
	 * @var WPML_TM_ATE_API
	 */
	private $ateApi;

	/**
	 * @var WPML_TM_Jobs_Repository
	 */
	private $jobsRepository;

	const PARAM_ATE_JOB_ID = 'ateJobId';
	const PARAM_WPML_JOB_ID = 'jobId';

	public function __construct( Adaptor $adaptor, WPML_TM_ATE_API $ateApi, WPML_TM_ATE_Jobs $ateJobs ) {
		parent::__construct( $adaptor );

		$this->ateApi  = $ateApi;
		$this->ateJobs = $ateJobs;
		$this->jobsRepository = wpml_tm_get_jobs_repository();
	}

	/**
	 * @return array
	 */
	public function get_routes() {
		return [
			[
				'route' => WPML_TM_ATE_AMS_Endpoints::FIX_JOB,
				'args'  => [
					'methods'  => 'GET',
					'callback' => [ $this, 'fix_job' ],
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

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return bool[]
	 */
	public function fix_job( WP_REST_Request $request ) {
		try {
			$ateJobId = $request->get_param( self::PARAM_ATE_JOB_ID );
			$wpmlJobId = $request->get_param( self::PARAM_WPML_JOB_ID );

			$processedJobResult = $this->process( $ateJobId, $wpmlJobId );

			if ( $processedJobResult ) {
				return [ 'completed' => true, 'error' => false ];
			}
		} catch ( \Exception $e ) {
			$this->logException( $e, [ 'ateJobId' => $ateJobId, 'wpmlJobId' => $wpmlJobId ] );
			return [ 'completed' => false, 'error' => true ];
		}
		return [ 'completed' => false, 'error' => false ];
	}

	/**
	 * Processes the job status.
	 *
	 * @param $ateJobId
	 * @param $wpmlJobId
	 *
	 * @return bool
	 * @throws RequestException
	 */
	public function process( $ateJobId, $wpmlJobId ) {
		$ateJob = $this->ateApi->get_job( $ateJobId )->$ateJobId;
		$xliffUrl = Obj::prop('translated_xliff', $ateJob);

		if ( $xliffUrl ) {
			$xliffContent = $this->ateApi->get_remote_xliff_content( $xliffUrl, [ 'jobId' => $wpmlJobId, 'ateJobId' => $ateJobId ] );
			$receivedWpmlJobId    = $this->ateJobs->apply( $xliffContent );

			if ( $receivedWpmlJobId && intval( $receivedWpmlJobId ) !== intval( $wpmlJobId ) ) {
				$error_message = sprintf( 'The received wpmlJobId (%s) does not match (%s).', $receivedWpmlJobId, $wpmlJobId );
				throw new \Exception( $error_message );
			}

			if ( $receivedWpmlJobId ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param \Exception $e
	 * @param array|null  $job
	 */
	private function logException( \Exception $e, $job = null ) {
		$entry              = new Entry();
		$entry->description = $e->getMessage();

		if ( $job ) {
			$entry->ateJobId  = Obj::prop('ateJobId', $job);
			$entry->wpmlJobId = Obj::prop('wpmlJobId', $job);
			$entry->extraData = [ 'downloadUrl' => Obj::prop('url', $job) ];
		}

		if ( $e instanceof RequestException ) {
			$entry->eventType = EventsTypes::SERVER_XLIFF;
		} else {
			$entry->eventType = EventsTypes::JOB_DOWNLOAD;
		}

		wpml_tm_ate_ams_log( $entry );
	}
}
