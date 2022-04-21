<?php

use WPML\FP\Cast;
use WPML\FP\Maybe;
use WPML\TM\ATE\JobRecords;
use function WPML\FP\pipe;
use function WPML\FP\partialRight;
use WPML\FP\Obj;
use WPML\FP\Logic;
use WPML\FP\Fns;
use function \WPML\FP\invoke;

/**
 * @author OnTheGo Systems
 */
class WPML_TM_ATE_Jobs {

	/** @var JobRecords $records */
	private $records;

	/**
	 * WPML_TM_ATE_Jobs constructor.
	 *
	 * @param JobRecords $records
	 */
	public function __construct( JobRecords $records ) {
		$this->records = $records;
	}

	/**
	 * @param int $wpml_job_id
	 *
	 * @return int
	 */
	public function get_ate_job_id( $wpml_job_id ) {
		$wpml_job_id = (int) $wpml_job_id;

		return $this->records->get_ate_job_id( $wpml_job_id );
	}

	/**
	 * @param int $ate_job_id
	 *
	 * @return int|null
	 */
	public function get_wpml_job_id( $ate_job_id ) {
		return Maybe::fromNullable( $ate_job_id )
		            ->map( Cast::toInt() )
		            ->map( [ $this->records, 'get_data_from_ate_job_id' ] )
		            ->map( Obj::prop( 'wpml_job_id' ) )
		            ->map( Cast::toInt() )
		            ->getOrElse( null );
	}

	/**
	 * @param int   $wpml_job_id
	 * @param array $ate_job_data
	 */
	public function store( $wpml_job_id, $ate_job_data ) {
		$this->records->store( (int) $wpml_job_id, $ate_job_data );
	}

	/**
	 * We update the status from ATE only for non-completed ATE statuses
	 * in all other cases, we mark the job as completed when we receive it
	 * from ATE in `WPML_TM_ATE_Jobs::apply` which calls `wpml_tm_save_data`.
	 *
	 * @param int $wpml_job_id
	 * @param int $ate_status
	 */
	public function set_wpml_status_from_ate( $wpml_job_id, $ate_status ) {
		$ate_status = (int) $ate_status;

		switch ( $ate_status ) {
			case WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_CREATED:
				$wpml_status = ICL_TM_WAITING_FOR_TRANSLATOR;
				break;

			case WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_TRANSLATING:
				$wpml_status = ICL_TM_IN_PROGRESS;
				break;

			default:
				$wpml_status = null;
		}

		if ( $wpml_status ) {
			WPML_TM_Update_Translation_Status::by_job_id( $wpml_job_id, (int) $wpml_status );
		}
	}

	/**
	 * @todo: Check possible duplicated code / We already have functionality to import XLIFF files from Translator's queue
	 *
	 * @param string $xliff
	 *
	 * @return bool|int
	 * @throws \Requests_Exception|Exception
	 */
	public function apply( $xliff ) {
		$factory       = wpml_tm_load_job_factory();
		$xliff_factory = new WPML_TM_Xliff_Reader_Factory( $factory );
		$xliff_reader  = $xliff_factory->general_xliff_reader();
		$job_data      = $xliff_reader->get_data( $xliff );
		if ( is_wp_error( $job_data ) ) {
			throw new Requests_Exception( $job_data->get_error_message(), $job_data->get_error_code() );
		}

		kses_remove_filters();
		$job_data    = $this->filterJobData( $job_data );
		$wpml_job_id = $job_data['job_id'];

		try {
			$is_saved = wpml_tm_save_data( $job_data, false );
		} catch ( Exception $e ) {
			throw new Exception(
				'The XLIFF file could not be applied to the content of the job ID: ' . $wpml_job_id,
				$e->getCode()
			);
		}

		kses_init();

		return $is_saved ? $wpml_job_id : false;
	}

	private function filterJobData( $jobData ) {
		/**
		 * It lets modify $job_data, which is especially usefull when we want to alter `data` of field.
		 *
		 * @param array    $jobData              {
		 *
		 * @type int       $job_id
		 * @type array fields {
		 * @type string    $data                 Translated content
		 * @type int       $finished
		 * @type int       $tid
		 * @type string    $field_type
		 * @type string    $format
		 *    }
		 * @type int       $complete
		 * }
		 *
		 * @param callable $getJobTargetLanguage The callback which expects $jobId as parameter
		 *
		 * @since 2.10.0
		 */
		$filteredJobData = apply_filters(
			'wpml_tm_ate_job_data_from_xliff',
			$jobData,
			$this->getJobTargetLanguage()
		);

		if ( array_key_exists( 'job_id', $filteredJobData ) && array_key_exists( 'fields', $filteredJobData ) ) {
			$jobData = $filteredJobData;
		}

		return $jobData;
	}

	/**
	 * getJobTargetLanguage :: void → ( object → string|null )
	 *
	 * @return callback
	 */
	private function getJobTargetLanguage() {
		// $getJobEntityById :: int -> \WPML_TM_Job_Entity|false
		$getJobEntityById = partialRight( [
			wpml_tm_get_jobs_repository(),
			'get_job'
		], \WPML_TM_Job_Entity::POST_TYPE );
		// $getTargetLangIfEntityExists :: \WPML_TM_Job_Entity|false -> string|null
		$getTargetLangIfEntityExists = Logic::ifElse( Fns::identity(), invoke( 'get_target_language' ), Fns::always( null ) );

		return pipe( Obj::prop( 'rid' ), $getJobEntityById, $getTargetLangIfEntityExists );
	}

	/**
	 * @param int $wpml_job_id
	 *
	 * @return bool
	 */
	public function is_editing_job( $wpml_job_id ) {
		return $this->records->is_editing_job( $wpml_job_id );
	}

	/**
	 * @param array $wpml_job_ids
	 */
	public function warm_cache( array $wpml_job_ids ) {
		$this->records->warmCache( $wpml_job_ids );
	}
}
