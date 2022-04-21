<?php

class WPML_TM_Jobs_Collection implements IteratorAggregate, Countable {
	/** @var WPML_TM_Job_Entity[] */
	private $jobs = array();

	public function __construct( array $jobs ) {
		foreach ( $jobs as $job ) {
			if ( $job instanceof WPML_TM_Job_Entity ) {
				$this->add( $job );
			}
		}
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 */
	private function add( WPML_TM_Job_Entity $job ) {
		$this->jobs[] = $job;
	}

	/**
	 * @param int $tp_id
	 *
	 * @return null|WPML_TM_Job_Entity
	 */
	public function get_by_tp_id( $tp_id ) {
		foreach ( $this->jobs as $job ) {
			if ( $tp_id === $job->get_tp_id() ) {
				return $job;
			}
		}

		return null;
	}

	/**
	 * @param callable $callback
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	public function filter( $callback ) {
		return new WPML_TM_Jobs_Collection( array_filter( $this->jobs, $callback ) );
	}

	/**
	 * @param array|int $status
	 * @param bool      $exclude
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	public function filter_by_status( $status, $exclude = false ) {
		if ( ! is_array( $status ) ) {
			$status = array( $status );
		}

		$result = array();
		if ( $exclude ) {
			foreach ( $this->jobs as $job ) {
				if ( ! in_array( $job->get_status(), $status, true ) ) {
					$result[] = $job;
				}
			}
		} else {
			foreach ( $this->jobs as $job ) {
				if ( in_array( $job->get_status(), $status, true ) ) {
					$result[] = $job;
				}
			}
		}

		return new WPML_TM_Jobs_Collection( $result );
	}

	/**
	 * @param callable $callback
	 * @param bool     $return_job_collection
	 *
	 * @return array|WPML_TM_Jobs_Collection
	 */
	public function map( $callback, $return_job_collection = false ) {
		$mapped_result = array_map( $callback, $this->jobs );

		return $return_job_collection ? new WPML_TM_Jobs_Collection( $mapped_result ) : $mapped_result;
	}

	public function map_to_property( $property ) {
		$method = 'get_' . $property;
		$result = array();

		foreach ( $this->jobs as $job ) {
			if ( ! method_exists( $job, $method ) ) {
				throw new InvalidArgumentException( 'Property ' . $property . ' does not exist' );
			}

			$result[] = $job->{$method}();
		}

		return $result;
	}

	/**
	 * @param $jobs
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	public function append( $jobs ) {
		if ( $jobs instanceof WPML_TM_Jobs_Collection ) {
			$jobs = $jobs->toArray();
		}

		return new WPML_TM_Jobs_Collection( array_merge( $this->jobs, $jobs ) );
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->jobs );
	}

	public function toArray() {
		return $this->jobs;
	}

	public function count() {
		return count( $this->jobs );
	}
}
