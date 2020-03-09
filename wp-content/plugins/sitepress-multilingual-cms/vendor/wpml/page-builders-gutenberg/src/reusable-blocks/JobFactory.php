<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class JobFactory {
	/** @var \WPML_Translation_Job_Factory $job_factory */
	private $job_factory;

	public function get_translation_job( $job_id ) {
		if ( ! $this->job_factory ) {
			$this->job_factory = \WPML\Container\make( '\WPML_Translation_Job_Factory' );
		}

		return $this->job_factory->get_translation_job( $job_id );
	}
}

