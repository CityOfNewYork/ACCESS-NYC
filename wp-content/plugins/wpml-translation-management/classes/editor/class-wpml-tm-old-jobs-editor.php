<?php

class WPML_TM_Old_Jobs_Editor {

	const OPTION_NAME = 'wpml-old-jobs-editor';

	/** @var wpdb */
	private $wpdb;

	/** @var WPML_Translation_Job_Factory */
	private $job_factory;

	public function __construct( WPML_Translation_Job_Factory $job_factory ) {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->job_factory = $job_factory;
	}


	/**
	 * @param int $job_id
	 *
	 * @return null|string
	 */
	public function get( $job_id ) {
		$current_editor = $this->get_current_editor( $job_id );

		if ( WPML_TM_Editors::NONE === $current_editor || WPML_TM_Editors::ATE === $current_editor ) {
			return $current_editor;
		} else {
			return get_option( self::OPTION_NAME, null );
		}
	}

	public function set( $job_id, $editor ) {
		$this->job_factory->update_job_data( $job_id, array( 'editor' => $editor ) );
	}


	/**
	 * @param int $job_id
	 *
	 * @return null|string
	 */
	private function get_current_editor( $job_id ) {
		$sql = "SELECT editor FROM {$this->wpdb->prefix}icl_translate_job WHERE job_id = %d";

		return $this->wpdb->get_var( $this->wpdb->prepare( $sql, $job_id ) );
	}
}