<?php

namespace WPML\TM\Menu\TranslationQueue;

use WPML\FP\Obj;

class PostTypeFilters {
	/** @var \WPML_TM_Jobs_Repository */
	private $jobsRepository;

	/**
	 * @param \WPML_TM_Jobs_Repository $jobsRepository
	 */
	public function __construct( \WPML_TM_Jobs_Repository $jobsRepository ) {
		$this->jobsRepository = $jobsRepository;
	}


	public function get( array $filters ) {
		global $sitepress;

		$searchParams = new \WPML_TM_Jobs_Search_Params();

		$searchParams = $this->addFilteringConditions( $searchParams, $filters );
		$searchParams->set_columns_to_select([
			"DISTINCT SUBSTRING_INDEX(translations.element_type, '_', 1) AS element_type_prefix",
			"translations.element_type AS original_post_type"
		]);

		$job_types = $this->jobsRepository->get($searchParams);

		$post_types = $sitepress->get_translatable_documents( true );
		$post_types = apply_filters( 'wpml_get_translatable_types', $post_types );
		$output     = [];

		foreach ( $job_types as $job_type ) {
			$type = $job_type->original_post_type;
			$name = $type;
			switch ( $job_type->element_type_prefix ) {
				case 'post':
					$type = substr( $type, 5 );
					break;

				case 'package':
					$type = substr( $type, 8 );
					break;

				case 'st-batch':
					$type = 'strings';
					$name = __( 'Strings', 'wpml-translation-management' );
					break;
			}

			$output[ $job_type->element_type_prefix . '_' . $type ] =
				Obj::pathOr( $name, [ $type, 'labels', 'singular_name' ], $post_types );
		}

		return $output;
	}

	/**
	 * @param \WPML_TM_Jobs_Search_Params $searchParams
	 * @param array $filters
	 *
	 * @return \WPML_TM_Jobs_Search_Params
	 */
	private function addFilteringConditions( \WPML_TM_Jobs_Search_Params $searchParams, array $filters ) {
		global $wpdb;

		$where[] = $wpdb->prepare( ' status NOT IN ( %d, %d )', ICL_TM_NOT_TRANSLATED, ICL_TM_ATE_CANCELLED );

		$translator = (int) Obj::prop( 'translator_id', $filters );
		if ( $translator ) {
			$where[] = $wpdb->prepare( '(translate_job.translator_id = %d OR translate_job.translator_id = 0 OR translate_job.translator_id IS NULL)', $translator );
		}

		$status = (int) Obj::prop( 'status', $filters );
		if ( $status ) {
			if ( $status === ICL_TM_NEEDS_REVIEW ) {
				$searchParams->set_needs_review( true );
			} else {
				$searchParams->set_status( [ $status ] );
				$searchParams->set_needs_review( false );
			}
		}

		$from = Obj::prop( 'from', $filters );
		if ( $from ) {
			$searchParams->set_source_language( $from );
		}

		$to = Obj::prop( 'to', $filters );
		if ( $to ) {
			$searchParams->set_target_language( $to );
		}

		$type = Obj::prop( 'type', $filters );
		if ( $type ) {
			$searchParams->set_element_type( $type );
		}

		$searchParams->set_custom_where_conditions( $where );

		return $searchParams;
	}
}