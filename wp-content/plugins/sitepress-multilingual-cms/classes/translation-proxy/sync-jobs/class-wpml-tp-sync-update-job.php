<?php

use WPML\FP\Relation;
use \WPML\LIB\WP\Post;

class WPML_TP_Sync_Update_Job {

	private $strategies = array(
		WPML_TM_Job_Entity::POST_TYPE    => 'update_post_job',
		WPML_TM_Job_Entity::STRING_TYPE  => 'update_string_job',
		WPML_TM_Job_Entity::PACKAGE_TYPE => 'update_post_job',
		WPML_TM_Job_Entity::STRING_BATCH => 'update_post_job',
	);

	/** @var wpdb */
	private $wpdb;

	/** @var SitePress */
	private $sitepress;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress ) {
		$this->wpdb      = $wpdb;
		$this->sitepress = $sitepress;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 */
	public function update_state( WPML_TM_Job_Entity $job ) {
		if ( ! array_key_exists( $job->get_type(), $this->strategies ) ) {
			return $job;
		}

		$method = $this->strategies[ $job->get_type() ];

		return call_user_func( array( $this, $method ), $job );
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 */
	private function update_string_job( WPML_TM_Job_Entity $job ) {
		if ( $job->get_tp_id() ) {
			$this->wpdb->update(
				$this->wpdb->prefix . 'icl_core_status',
				array(
					'status'      => $job->get_status(),
					'tp_revision' => $job->get_revision(),
					'ts_status'   => $this->get_ts_status_in_ts_format( $job ),
				),
				array( 'rid' => $job->get_tp_id() )
			);
		}

		$data = array(
			'status' => $job->get_status(),
		);
		if ( ICL_TM_NOT_TRANSLATED === $job->get_status() ) {
			$data['translator_id'] = null;
		}

		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_string_translations',
			$data,
			array( 'id' => $job->get_id() )
		);

		icl_update_string_status( $job->get_original_element_id() );

		return $job;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return WPML_TM_Job_Entity
	 */
	private function update_post_job( WPML_TM_Job_Entity $job ) {
		$job_id = $job->get_id();

		if ( $job->get_status() === ICL_TM_NOT_TRANSLATED ) {
			$prev_status = $this->get_job_prev_status( $job_id );
			if ( $prev_status && Relation::propEq( 'needs_update', '1', $prev_status ) ) {
				$this->wpdb->update( $this->wpdb->prefix . 'icl_translation_status', $prev_status, [ 'rid' => $job_id ] );
				$job->set_needs_update( true );
				return $job;
			}
		}

		$this->wpdb->update(
			$this->wpdb->prefix . 'icl_translation_status',
			array(
				'status'      => $job->get_status(),
				'tp_revision' => $job->get_revision(),
				'ts_status'   => $this->get_ts_status_in_ts_format( $job ),
			),
			array( 'rid' => $job_id )
		);

		if (
			ICL_TM_NOT_TRANSLATED === $job->get_status()
			&& ( $post_type = Post::getType( $job->get_original_element_id() ) )
		) {
			$this->sitepress->delete_orphan_element(
				$job->get_original_element_id(),
				'post_' . $post_type,
				$job->get_target_language()
			);
		}

		return $job;
	}

	private function get_job_prev_status( $job_id ) {
		$previous_state = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT _prevstate
					FROM {$this->wpdb->prefix}icl_translation_status
					WHERE rid=%d
					LIMIT 1",
				$job_id
			)
		);

		return $previous_state
			? unserialize( $previous_state )
			: null;
	}

	/**
	 * In the db, we store the exact json format that we get from TS. It includes an extra ts_status key
	 *
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return string
	 */
	private function get_ts_status_in_ts_format( WPML_TM_Job_Entity $job ) {
		$ts_status = $job->get_ts_status();

		return $ts_status ? wp_json_encode( array( 'ts_status' => json_decode( (string) $ts_status ) ) ) : null;
	}
}
