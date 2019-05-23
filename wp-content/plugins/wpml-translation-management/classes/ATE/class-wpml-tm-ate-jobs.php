<?php

/**
 * @author OnTheGo Systems
 */
class WPML_TM_ATE_Jobs {

	/** @var WPML_TM_ATE_Job_Records $records */
	private $records;

	/**
	 * WPML_TM_ATE_Jobs constructor.
	 *
	 * @param WPML_TM_ATE_Job_Records $records
	 */
	public function __construct( WPML_TM_ATE_Job_Records $records ) {
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
	 * @param int $wpml_job_id
	 *
	 * @return int
	 */
	public function get_ate_job_progress( $wpml_job_id ) {
		$wpml_job_id = (int) $wpml_job_id;

		return $this->records->get_ate_job_progress( $wpml_job_id );
	}

	/**
	 * @param int $ate_job_id
	 *
	 * @return int
	 */
	public function get_wpml_job_id( $ate_job_id ) {
		$ate_job_id = (int) $ate_job_id;

		$ate_job = $this->records->get_data_from_ate_job_id( $ate_job_id );

		$wpml_job_id = null;

		if ( array_key_exists( 'wpml_job_id', $ate_job ) ) {
			$wpml_job_id = (int) $ate_job['wpml_job_id'];
		}

		return $wpml_job_id;
	}

	/**
	 * @param int $wpml_job_id
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
	 * @return bool
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

		try {
			$is_saved = wpml_tm_save_data( $job_data, false );
		} catch ( Exception $e ) {
			throw new Exception(
				'The XLIFF file could not be applied to the content of the job ID: ' . $job_data['job_id'],
				$e->getCode()
			);
		}

		kses_init();

		return $is_saved;
	}

	/**
	 * @param int $wpml_job_id
	 *
	 * @return bool
	 */
	public function is_editing_job( $wpml_job_id ) {
		return $this->records->is_editing_job( $wpml_job_id );
	}
}
