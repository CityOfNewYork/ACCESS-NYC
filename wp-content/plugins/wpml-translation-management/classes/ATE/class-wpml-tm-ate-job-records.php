<?php

class WPML_TM_ATE_Job_Records {

	const DELIVERING_JOB_STATUS = WPML_TM_ATE_AMS_Endpoints::ATE_JOB_STATUS_DELIVERING;
	const WPML_TM_ATE_JOB_RECORDS = 'WPML_TM_ATE_JOBS';

	const FIELD_ATE_JOB_ID = 'ate_job_id';
	const FIELD_IS_EDITING = 'is_editing';
	const FIELD_PROGRESS   = 'progress_details';

	/**
	 * This field is not used anymore
	 */
	const FIELD_OLD_ATE_JOB_ID = 'ateJobId';

	private $ate_jobs = array();
	private $data = array();

	/**
	 * @param int $wpml_job_id
	 *
	 * @return array
	 */
	public function get_ate_job_progress( $wpml_job_id ) {
		$progress = $this->get_ate_job_field( $wpml_job_id, self::FIELD_PROGRESS );

		return is_array( $progress ) ? $progress : array();
	}

	/**
	 * @param int    $wpml_job_id
	 * @param string $field_name
	 * @param mixed  $field_value
	 *
	 * @return bool
	 */
	public function set_ate_job_field( $wpml_job_id, $field_name, $field_value ) {
		$job_id = (int) $wpml_job_id;

		$this->read_option_value();

		$data = array();
		if ( array_key_exists( $job_id, $this->data ) ) {
			$data = $this->data[ $job_id ];
		}
		$data[ $field_name ] = $field_value;
		try {
			$this->store( $wpml_job_id, $data );

			return true;
		} catch ( Exception $ex ) {
			return false;
		}
	}

	/**
	 * @param int $ate_job_id
	 *
	 * @return array|null
	 */
	public function get_data_from_ate_job_id( $ate_job_id ) {
		$ate_job_id = (int) $ate_job_id;
		if ( ! array_key_exists( $ate_job_id, $this->ate_jobs ) ) {
			$this->read_option_value();
			foreach ( $this->data as $job_id => $job_data ) {
				$this->ate_jobs[ $ate_job_id ] = array(
					'wpml_job_id'  => $job_id,
					'ate_job_data' => $job_data
				);
			}
		}

		if ( array_key_exists( $ate_job_id, $this->ate_jobs ) ) {
			return $this->ate_jobs[ $ate_job_id ];
		}

		return null;
	}

	/**
	 * @param int   $wpml_job_id
	 * @param array $ate_job_data
	 */
	public function store( $wpml_job_id, array $ate_job_data ) {
		$wpml_job_id  = (int) $wpml_job_id;
		$ate_job_data = $this->sanitize_data( $ate_job_data );

		$this->read_option_value();

		if ( isset( $this->data[ $wpml_job_id ] ) ) {
			$this->data[ $wpml_job_id ] = array_merge( $this->data[ $wpml_job_id ], $ate_job_data );
		} else {
			$this->data[ $wpml_job_id ] = $ate_job_data;
		}

		update_option( self::WPML_TM_ATE_JOB_RECORDS, $this->data );
	}

	private function read_option_value() {
		$this->data = get_option( self::WPML_TM_ATE_JOB_RECORDS, array() );

		if ( ! is_array( $this->data ) ) {
			$this->data = array();
			update_option( self::WPML_TM_ATE_JOB_RECORDS, $this->data );
		}

		return $this->data;
	}

	/**
	 * @param int $wpml_job_id
	 *
	 * @return int
	 */
	public function get_ate_job_id( $wpml_job_id ) {
		return $this->get_ate_job_field( $wpml_job_id, self::FIELD_ATE_JOB_ID );
	}

	/**
	 * @param int  $wpml_job_id
	 * @param bool $is_editing
	 */
	public function set_editing_job( $wpml_job_id, $is_editing ) {
		$this->set_ate_job_field( $wpml_job_id, self::FIELD_IS_EDITING, $is_editing );
	}

	/**
	 * @param int $wpml_job_id
	 *
	 * @return bool
	 */
	public function is_editing_job( $wpml_job_id ) {
		return (bool) $this->get_ate_job_field( $wpml_job_id, self::FIELD_IS_EDITING );
	}

	/**
	 * @param int    $wpml_job_id
	 * @param string $field_name
	 *
	 * @return mixed
	 */
	private function get_ate_job_field( $wpml_job_id, $field_name ) {
		$this->read_option_value();

		$job_id = (int) $wpml_job_id;
		if ( ! array_key_exists( $job_id, $this->data ) ) {
			$data = apply_filters( 'wpml_tm_ate_job_data_fallback', array(), $job_id );
			if ( $data ) {
				try {
					$this->store( $job_id, $data );
				} catch ( Exception $e ) {
					$error_log = new WPML_TM_ATE_API_Error();
					$error_log->log( $e->getMessage() );
				}
			}

			$this->data[ $job_id ] = $data;
		}

		if ( isset( $this->data[ $job_id ][ $field_name ] ) ) {
			return $this->data[ $job_id ][ $field_name ];
		}

		return '';
	}

	/**
	 * @param array $ate_data
	 *
	 * @return array
	 */
	private function sanitize_data( array $ate_data ) {
		$sanitized_data = filter_var_array(
			$ate_data,
			array(
				self::FIELD_ATE_JOB_ID => FILTER_SANITIZE_NUMBER_INT,
				self::FIELD_IS_EDITING => FILTER_VALIDATE_BOOLEAN,
				self::FIELD_PROGRESS   => array(
					'filter' => FILTER_DEFAULT,
					'flags'  => FILTER_REQUIRE_ARRAY,
				),
			)
		);

		foreach ( $sanitized_data as $key => $data ) {
			if ( null === $data ) {
				unset( $sanitized_data[ $key ] );
			}
		}

		return $sanitized_data;
	}

	public function cleanup_data() {
		$this->read_option_value();

		foreach ( $this->data as $wpml_job_id => $ate_job_data ) {
			if ( ! isset( $ate_job_data[ self::FIELD_ATE_JOB_ID ] )
			     && isset( $ate_job_data[ self::FIELD_OLD_ATE_JOB_ID ] )
			) {
				$ate_job_data[ self::FIELD_ATE_JOB_ID ] = $ate_job_data[ self::FIELD_OLD_ATE_JOB_ID ];
			}

			$this->data[ $wpml_job_id ] = $this->sanitize_data( $ate_job_data );
		}

		update_option( self::WPML_TM_ATE_JOB_RECORDS, $this->data );
	}
}
