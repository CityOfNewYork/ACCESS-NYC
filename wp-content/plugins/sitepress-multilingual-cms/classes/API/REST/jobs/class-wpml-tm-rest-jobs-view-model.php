<?php

use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\TM\API\Jobs;
use WPML\TM\ATE\Review\PreviewLink;
use WPML\TM\ATE\Review\ReviewStatus;
use WPML\TM\Jobs\Utils\ElementLink;

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

	/** @var ElementLink $element_link */
	private $element_link;

	/**
	 * @param WPML_TM_Rest_Jobs_Translation_Service $translation_service
	 * @param WPML_TM_Rest_Jobs_Element_Info $element_info
	 * @param WPML_TM_Rest_Jobs_Language_Names $language_names
	 * @param WPML_TM_Rest_Job_Translator_Name $translator_name
	 * @param WPML_TM_Rest_Job_Progress $progress
	 * @param ElementLink $element_link
	 */
	public function __construct(
		WPML_TM_Rest_Jobs_Translation_Service $translation_service,
		WPML_TM_Rest_Jobs_Element_Info $element_info,
		WPML_TM_Rest_Jobs_Language_Names $language_names,
		WPML_TM_Rest_Job_Translator_Name $translator_name,
		WPML_TM_Rest_Job_Progress $progress,
		ElementLink $element_link
	) {
		$this->translation_service = $translation_service;
		$this->element_info        = $element_info;
		$this->language_names      = $language_names;
		$this->translator_name     = $translator_name;
		$this->progress            = $progress;
		$this->element_link        = $element_link;
	}

	/**
	 * @param WPML_TM_Jobs_Collection $jobs
	 * @param int $total_jobs_count
	 * @param WPML_TM_Jobs_Search_Params $jobs_search_params
	 *
	 * @return array
	 */
	public function build( WPML_TM_Jobs_Collection $jobs, $total_jobs_count, WPML_TM_Jobs_Search_Params $jobs_search_params ) {
		$result = [ 'jobs' => [] ];

		foreach ( $jobs as $job ) {
			$result['jobs'][] = $this->map_job( $job, $jobs_search_params );
		}

		$result['total'] = $total_jobs_count;

		return $result;
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 * @param WPML_TM_Jobs_Search_Params $jobs_search_params
	 *
	 * @return array
	 */
	private function map_job( WPML_TM_Job_Entity $job, WPML_TM_Jobs_Search_Params $jobs_search_params ) {
		$extra_data = [];
		$viewUrl    = '';

		if ( $job instanceof WPML_TM_Post_Job_Entity ) {
			$extra_data['icl_translate_job_id'] = $job->get_translate_job_id();
			$extra_data['editor_job_id']        = $job->get_editor_job_id();

			$viewUrl = $this->getViewUrl( $job, $jobs_search_params );
		}

		return [
			'id'                     => $job->get_rid(),
			'type'                   => $job->get_type(),
			'tp_id'                  => $job->get_tp_id(),
			'status'                 => $job->get_status(),
			'needs_update'           => $job->does_need_update(),
			'review_status'          => $job->get_review_status(),
			'language_codes'         => [
				'source' => $job->get_source_language(),
				'target' => $job->get_target_language(),
			],
			'languages'              => [
				'source' => $this->language_names->get( $job->get_source_language() ),
				'target' => $this->language_names->get( $job->get_target_language() ),
			],
			'translation_service_id' => $job->get_translation_service(),
			'translation_service'    => $this->translation_service->get_name( $job->get_translation_service() ),
			'sent_date'              => $job->get_sent_date()->format( 'Y-m-d' ),
			'deadline'               => $job->get_deadline() ? $job->get_deadline()->format( 'Y-m-d' ) : '',
			'ts_status'              => (string) $job->get_ts_status(),
			'element'                => $this->element_info->get( $job ),
			'translator_name'        => $job->get_translator_id() ? $this->translator_name->get( $job->get_translator_id() ) : '',
			'translator_id'          => $job->get_translator_id() ? (int) $job->get_translator_id() : '',
			'is_ate_job'             => $job->is_ate_job(),
			'automatic'              => $job->is_automatic(),
			'progress'               => $this->progress->get( $job ),
			'batch'                  => [
				'id'    => $job->get_batch()->get_id(),
				'name'  => $job->get_batch()->get_name(),
				'tp_id' => $job->get_batch()->get_tp_id(),
			],
			'extra_data'             => $extra_data,
			'edit_url'               => $this->get_edit_url( $job ),
			'view_url'               => $viewUrl,
			'view_original_url'      => $this->element_link->getOriginal( $job ),
		];
	}

	/**
	 * @param WPML_TM_Job_Entity $job
	 *
	 * @return mixed|string|void
	 */
	private function get_edit_url( $job ) {
		$edit_url = '';
		if ( $job->get_original_element_id() ) {

			$jobId = $job instanceof WPML_TM_Post_Job_Entity ? $job->get_translate_job_id() : $job->get_rid();

			$translation_queue_page = admin_url( 'admin.php?page='
			                                     . WPML_TM_FOLDER
			                                     . '/menu/translations-queue.php&job_id='
			                                     . $jobId );
			$edit_url               = apply_filters( 'icl_job_edit_url', $translation_queue_page, $jobId );
		}

		return $edit_url;
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 * @param WPML_TM_Jobs_Search_Params $jobs_search_params
	 *
	 * @return string
	 */
	private function getViewUrl( WPML_TM_Post_Job_Entity $job, WPML_TM_Jobs_Search_Params $jobs_search_params ) {
		$needsReview = Lst::includes( $job->get_review_status(), [
			ReviewStatus::NEEDS_REVIEW,
			ReviewStatus::EDITING
		] );

		return $needsReview ? $this->getReviewUrl( $job, $jobs_search_params ) : $this->element_link->getTranslation( $job );
	}

	/**
	 * @param WPML_TM_Post_Job_Entity $job
	 * @param WPML_TM_Jobs_Search_Params $jobs_search_params
	 *
	 * @return string
	 */
	private function getReviewUrl( WPML_TM_Post_Job_Entity $job, WPML_TM_Jobs_Search_Params $jobs_search_params ) {
		$translation     = PostTranslations::getInLanguage( $job->get_original_element_id(), $job->get_target_language() );
		$target_language = $jobs_search_params->get_target_language();
		$element_type    = $jobs_search_params->get_element_type();

		$filterParams = '';

		if ( is_string( $element_type ) && strlen( $element_type ) > 0 ) {
			$filterParams .= '&element_type=' . $element_type;
		}
		if ( is_array( $target_language ) && count( $target_language ) > 0 ) {
			$filterParams .= '&targetLanguages=' . implode( ',', $target_language );
		}

		$returnUrl    = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php' . $filterParams );

		return PreviewLink::getWithSpecifiedReturnUrl( $returnUrl, $translation->element_id, $job->get_translate_job_id() );
	}
}