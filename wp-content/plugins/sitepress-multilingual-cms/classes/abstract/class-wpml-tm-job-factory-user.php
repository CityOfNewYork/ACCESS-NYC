<?php

abstract class WPML_TM_Job_Factory_User {

	/** @var  WPML_Translation_Job_Factory $tm_job_factory */
	protected $job_factory;

	/**
	 * WPML_TM_Xliff_Reader constructor.
	 *
	 * @param WPML_Translation_Job_Factory $job_factory
	 */
	public function __construct( $job_factory ) {
		$this->job_factory = $job_factory;
	}
}
