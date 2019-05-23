<?php

class WPML_TM_Rest_Jobs_View_Model {
	/** @var WPML_TM_Rest_Jobs_Translation_Service */
	private $translation_service;

	/** @var WPML_TM_Rest_Jobs_Element_Info */
	private $element_info;

	/** @var WPML_TM_Rest_Jobs_Language_Names */
	private $language_names;

	/** @var WPML_TM_Rest_Job_Translator_Name */
	private $translator_name;

	/** @var WPML_TM_Rest_Job_Progress */
	private $progress;

	/**
	 * @param WPML_TM_Rest_Jobs_Translation_Service $translation_service
	 * @param WPML_TM_Rest_Jobs_Element_Info        $element_info
	 * @param SitePress                             $sitepress
	 */
	public function __construct(
		WPML_TM_Rest_Jobs_Translation_Service $translation_service,
		WPML_TM_Rest_Jobs_Element_Info $element_info,
		WPML_TM_Rest_Jobs_Language_Names $language_names,
		WPML_TM_Rest_Job_Translator_Name $translator_name,
		WPML_TM_Rest_Job_Progress $progress
	) {
		$this->translation_service = $translation_service;
		$this->element_info        = $element_info;
		$this->language_names      = $language_names;
		$this->translator_name     = $translator_name;
		$this->progress            = $progress;
	}

	/**
	 * @param WPML_TM_Jobs_Collection $jobs
	 * @param int                     $total_jobs_count
	 *
	 * @return array
	 */
	public function build( WPML_TM_Jobs_Collection $jobs, $total_jobs_count ) {
		$result = array( 'jobs' => array() );

		foreach ( $jobs as $job ) {
			$result['jobs'][] = $this->map_job( $job );
		}

		$result['total'] = $total_jobs_count;

		return $result;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return array
	 */
	private function map_job( WPML_TM_Job_Entity $job ) {
		$extra_data = array();
		if ( $job instanceof WPML_TM_Post_Job_Entity ) {
			$extra_data['icl_translate_job_id'] = $job->get_translate_job_id();
		}

		return array(
			'id'                     => $job->get_id(),
			'type'                   => $job->get_type(),
			'tp_id'                  => $job->get_tp_id(),
			'status'                 => $job->get_status(),
			'language_codes'         => array(
				'source' => $job->get_source_language(),
				'target' => $job->get_target_language(),
			),
			'languages'              => array(
				'source' => $this->language_names->get( $job->get_source_language() ),
				'target' => $this->language_names->get( $job->get_target_language() ),
			),
			'translation_service_id' => $job->get_translation_service(),
			'translation_service'    => $this->translation_service->get_name( $job->get_translation_service() ),
			'sent_date'              => $job->get_sent_date()->format( 'Y-m-d' ),
			'deadline'               => $job->get_deadline() ? $job->get_deadline()->format( 'Y-m-d' ) : '',
			'ts_status'              => (string) $job->get_ts_status(),
			'element'                => $this->element_info->get( $job->get_original_element_id(), $job->get_type() ),
			'translator_name'        => $job->get_translator_id() ? $this->translator_name->get( $job->get_translator_id() ) : '',
			'progress'               => $this->progress->get( $job ),
			'batch'                  => array(
				'id'    => $job->get_batch()->get_id(),
				'tp_id' => $job->get_batch()->get_tp_id(),
			),
			'extra_data'             => $extra_data,
		);
	}

}