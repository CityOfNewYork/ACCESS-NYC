<?php

class WPML_TP_Apply_Translation_Strategies {
	/** @var WPML_TP_Apply_Translation_Post_Strategy */
	private $post_strategy;

	/** @var WPML_TP_Apply_Translation_String_Strategy */
	private $string_strategy;

	/** @var wpdb */
	private $wpdb;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TP_Apply_Translation_Strategy
	 */
	public function get( WPML_TM_Job_Entity $job ) {
		switch ( $job->get_type() ) {
			case WPML_TM_Job_Entity::STRING_TYPE:
				return $this->get_string_strategy();
			case WPML_TM_Job_Entity::POST_TYPE:
			case WPML_TM_Job_Entity::PACKAGE_TYPE:
			case WPML_TM_Job_Entity::STRING_BATCH:
				return $this->get_post_strategy();
			default:
				throw new InvalidArgumentException( 'Job type: ' . $job->get_type() . ' is not supported' );
		}
	}

	/**
	 * @return WPML_TP_Apply_Translation_Post_Strategy
	 */
	private function get_post_strategy() {
		if ( ! $this->post_strategy ) {
			$this->post_strategy = new WPML_TP_Apply_Translation_Post_Strategy( wpml_tm_get_tp_jobs_api() );
		}

		return $this->post_strategy;
	}

	/**
	 * @return WPML_TP_Apply_Translation_String_Strategy
	 */
	private function get_string_strategy() {
		if ( ! $this->string_strategy ) {
			$this->string_strategy = new WPML_TP_Apply_Translation_String_Strategy(
				wpml_tm_get_tp_jobs_api(),
				$this->wpdb
			);
		}

		return $this->string_strategy;
	}
}
