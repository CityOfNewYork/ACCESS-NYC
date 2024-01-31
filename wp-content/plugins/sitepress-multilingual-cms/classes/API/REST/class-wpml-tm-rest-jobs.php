<?php
/**
 * WPML_TM_REST_Jobs class file.
 *
 * @package wpml-translation-management
 */

use WPML\FP\Obj;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\LIB\WP\User;
use WPML\TM\ATE\Review\Cancel;
use function WPML\FP\pipe;
use function WPML\FP\partial;
use function WPML\FP\invoke;
use function WPML\FP\curryN;

/**
 * Class WPML_TM_REST_Jobs
 */
class WPML_TM_REST_Jobs extends WPML_REST_Base {
	const CAPABILITY = 'translate';

	/**
	 * Jobs repository
	 *
	 * @var WPML_TM_Jobs_Repository
	 */
	private $jobs_repository;

	/**
	 * Rest jobs criteria parser
	 *
	 * @var WPML_TM_Rest_Jobs_Criteria_Parser
	 */
	private $criteria_parser;

	/**
	 * View model
	 *
	 * @var WPML_TM_Rest_Jobs_View_Model
	 */
	private $view_model;

	/**
	 * Update jobs synchronisation
	 *
	 * @var WPML_TP_Sync_Update_Job
	 */
	private $update_jobs;

	/**
	 * Last picked up jobs
	 *
	 * @var WPML_TM_Last_Picked_Up $wpml_tm_last_picked_up
	 */
	private $wpml_tm_last_picked_up;

	/**
	 * WPML_TM_REST_Jobs constructor.
	 *
	 * @param WPML_TM_Jobs_Repository           $jobs_repository        Jobs repository.
	 * @param WPML_TM_Rest_Jobs_Criteria_Parser $criteria_parser        Rest jobs criteria parser.
	 * @param WPML_TM_Rest_Jobs_View_Model      $view_model             View model.
	 * @param WPML_TP_Sync_Update_Job           $update_jobs            Update jobs synchronisation.
	 * @param WPML_TM_Last_Picked_Up            $wpml_tm_last_picked_up Last picked up jobs.
	 */
	public function __construct(
		WPML_TM_Jobs_Repository $jobs_repository,
		WPML_TM_Rest_Jobs_Criteria_Parser $criteria_parser,
		WPML_TM_Rest_Jobs_View_Model $view_model,
		WPML_TP_Sync_Update_Job $update_jobs,
		WPML_TM_Last_Picked_Up $wpml_tm_last_picked_up
	) {
		parent::__construct( 'wpml/tm/v1' );

		$this->jobs_repository        = $jobs_repository;
		$this->criteria_parser        = $criteria_parser;
		$this->view_model             = $view_model;
		$this->update_jobs            = $update_jobs;
		$this->wpml_tm_last_picked_up = $wpml_tm_last_picked_up;
	}


	/**
	 * Add hooks
	 */
	public function add_hooks() {
		$this->register_routes();
	}

	/**
	 * Register routes
	 */
	public function register_routes() {
		parent::register_route(
			'/jobs',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_jobs' ),
				'args'     => array(
					'local_job_ids'   => array(
						'type'              => 'string',
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'string' ),
					),
					'scope'           => array(
						'type'              => 'string',
						'validate_callback' => array( 'WPML_TM_Jobs_Search_Params', 'is_valid_scope' ),
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'string' ),
					),
					'id'              => array(
						'type'              => 'integer',
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'integer' ),
					),
					'title'           => array(
						'type'              => 'string',
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'string' ),
					),
					'source_language' => array(
						'type'              => 'string',
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'string' ),
					),
					'target_language' => array(
						'type'              => 'string',
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'string' ),
					),
					'status'          => array(
						'type'              => 'string',
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'string' ),
					),
					'needs_update'    => array(
						'type'              => 'string',
						'validate_callback' => array( 'WPML_TM_Jobs_Needs_Update_Param', 'is_valid' ),
					),
					'limit'           => array(
						'type'              => 'integer',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
					),
					'offset'          => array(
						'type'              => 'integer',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
					),
					'sorting'         => array(
						'validate_callback' => array( $this, 'validate_sorting' ),
					),
					'translated_by'   => array(
						'type'              => 'string',
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'string' ),
					),
					'sent_from'       => array(
						'type'              => 'string',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'date' ),
					),
					'sent_to'         => array(
						'type'              => 'string',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'date' ),
					),
					'deadline_from'   => array(
						'type'              => 'string',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'date' ),
					),
					'deadline_to'     => array(
						'type'              => 'string',
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'date' ),
					),
				),
			)
		);

		parent::register_route(
			'/jobs/assign',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'assign_job' ),
				'args'     => array(
					'jobId'        => array(
						'required'          => true,
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'integer' ),
					),
					'type'         => array(
						'required'          => false,
						'validate_callback' => [ WPML_TM_Job_Entity::class, 'is_type_valid' ],
					),
					'translatorId' => array(
						'required'          => true,
						'validate_callback' => array( 'WPML_REST_Arguments_Validation', 'integer' ),
						'sanitize_callback' => array( 'WPML_REST_Arguments_Sanitation', 'integer' ),
					),
				),
			)
		);

		parent::register_route(
			'/jobs/cancel',
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'cancel_jobs' ),
			)
		);
	}

	/**
	 * Get jobs
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return array|WP_Error
	 */
	public function get_jobs( WP_REST_Request $request ) {
		try {
			$criteria = $this->criteria_parser->build_criteria( $request );

			$model = $this->view_model->build(
				$this->jobs_repository->get( $criteria ),
				$this->jobs_repository->get_count( $criteria ),
				$criteria
			);

			$model['last_picked_up_date'] = $this->wpml_tm_last_picked_up->get();

			return $model;
		} catch ( Exception $e ) {
			return new WP_Error( 500, $e->getMessage() );
		}
	}

	/**
	 * Assign job.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return array
	 * @throws \InvalidArgumentException Exception on error.
	 */
	public function assign_job( WP_REST_Request $request ) {
		/**
		 * It can be job_id from icl_translate_job or id from icl_string_translations
		 *
		 * @var int $job_id
		 */
		$job_id       = $request->get_param( 'jobId' );
		$job_type     = $request->get_param( 'type' ) ? $request->get_param( 'type' ) : WPML_TM_Job_Entity::POST_TYPE;

		$assignJob = curryN( 4, 'wpml_tm_assign_translation_job');

		return Maybe::of( $request->get_param( 'translatorId' ) )
		                     ->filter( User::get() )
		                     ->map( $assignJob( $job_id, Fns::__, 'local', $job_type ) )
		                     ->map( Obj::objOf( 'assigned' ) )
		                     ->getOrElse( null );
	}

	/**
	 * Cancel job
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return array|WP_Error
	 */
	public function cancel_jobs( WP_REST_Request $request ) {
		try {
			// $validateParameter :: [id, type] -> bool
			$validateParameter = pipe( Obj::prop( 'type' ), [ \WPML_TM_Job_Entity::class, 'is_type_valid' ] );

			// $getJob :: [id, type] -> \WPML_TM_Job_Entity
			$getJob = Fns::converge( [ $this->jobs_repository, 'get_job' ], [ Obj::prop( 'id' ), Obj::prop( 'type' ) ] );

			// $jobEntityToArray :: \WPML_TM_Job_Entity -> [id, type]
			$jobEntityToArray = function ( \WPML_TM_Job_Entity $job ) {
				return [
					'id'   => $job->get_id(),
					'type' => $job->get_type(),
				];
			};

			$jobs = \wpml_collect( $request->get_json_params() )
				->filter( $validateParameter )
				->map( $getJob )
				->filter()
				->map( Fns::tap( invoke( 'set_status' )->with( ICL_TM_NOT_TRANSLATED ) ) )
				->map( Fns::tap( [ $this->update_jobs, 'update_state' ] ) );

			do_action( 'wpml_tm_jobs_cancelled', $jobs->toArray() );

			return $jobs->map( $jobEntityToArray )->values()->toArray();
		} catch ( Exception $e ) {
			return new WP_Error( 500, $e->getMessage() );
		}
	}

	/**
	 * Get allowed capabilities
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return array|string
	 */
	public function get_allowed_capabilities( WP_REST_Request $request ) {
		return [ User::CAP_ADMINISTRATOR, User::CAP_MANAGE_TRANSLATIONS, User::CAP_TRANSLATE ];
	}

	/**
	 * Validate sorting
	 *
	 * @param mixed $sorting Sorting parameters.
	 *
	 * @return bool
	 */
	public function validate_sorting( $sorting ) {
		if ( ! is_array( $sorting ) ) {
			return false;
		}

		try {
			foreach ( $sorting as $column => $asc_or_desc ) {
				new WPML_TM_Jobs_Sorting_Param( $column, $asc_or_desc );
			}
		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate job
	 *
	 * @param mixed $job Job.
	 *
	 * @return bool
	 */
	private function validate_job( $job ) {
		return is_array( $job ) && isset( $job['id'] ) && isset( $job['type'] ) && \WPML_TM_Job_Entity::is_type_valid( $job['type'] );
	}
}
