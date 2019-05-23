<?php

interface WPML_TP_Apply_Translation_Strategy {
	/**
	 * @param WPML_TM_Job_Entity             $job
	 * @param WPML_TP_Translation_Collection $translations
	 *
	 * @return void
	 */
	public function apply( WPML_TM_Job_Entity $job, WPML_TP_Translation_Collection $translations );
}