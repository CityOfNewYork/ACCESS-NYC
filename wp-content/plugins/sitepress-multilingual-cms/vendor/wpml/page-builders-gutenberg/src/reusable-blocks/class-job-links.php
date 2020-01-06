<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class JobLinks {

	/** @var JobFactory $job_factory */
	private $job_factory;

	public function __construct( JobFactory $job_factory ) {
		$this->job_factory = $job_factory;
	}

	/**
	 * @param array $job_ids
	 *
	 * @return \WPML\Collect\Support\Collection
	 */
	public function get( array $job_ids ) {
		return \wpml_collect( $job_ids )->map( function( $job_id ) {
			return $this->getJobEditLink( $job_id );
		} )->filter();
	}

	/**
	 * @param int $job_id
	 *
	 * @return string|null
	 */
	private function getJobEditLink( $job_id ) {
		$job = $this->job_factory->get_translation_job( $job_id );

		if ( ! $job || 'post_wp_block' !== $job->original_post_type ) {
			return null;
		}

		$job_edit_url = admin_url( 'admin.php?page='
		                           . WPML_TM_FOLDER
		                           . '/menu/translations-queue.php&job_id='
		                           . $job_id );
		$job_edit_url = apply_filters( 'icl_job_edit_url', $job_edit_url, $job_id );

		return '<a href="' . $job_edit_url . '" class="wpml-external-link" target="_blank">' . $job->title . '</a>';
	}
}
