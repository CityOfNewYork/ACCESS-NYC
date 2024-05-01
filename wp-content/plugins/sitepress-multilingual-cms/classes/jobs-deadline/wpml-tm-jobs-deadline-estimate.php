<?php

class WPML_TM_Jobs_Deadline_Estimate {

	const LATENCY_DAYS  = 1;
	const WORDS_PER_DAY = 1200;

	/** @var WPML_TM_Translatable_Element_Provider */
	private $translatable_element_provider;

	/** @var WPML_TM_Jobs_Repository */
	private $jobs_repository;

	public function __construct(
		WPML_TM_Translatable_Element_Provider $translatable_element_provider,
		WPML_TM_Jobs_Repository $jobs_repository
	) {
		$this->translatable_element_provider = $translatable_element_provider;
		$this->jobs_repository               = $jobs_repository;
	}

	/**
	 * @param array $basket
	 * @param array $translator_options
	 *
	 * @return string
	 */
	public function get( array $basket, array $translator_options ) {
		$pending_jobs = $this->get_pending_jobs_for_translator( $translator_options );

		$words_per_langs = $this->get_pending_words_per_langs( $pending_jobs );
		$words_per_langs = $this->add_basket_words_per_langs( $basket, $words_per_langs );

		$max_words_in_lang = 0;

		if ( $words_per_langs ) {
			$max_words_in_lang = max( $words_per_langs );
		}

		$estimated_days  = ceil( $max_words_in_lang / self::WORDS_PER_DAY );
		$estimated_days += self::LATENCY_DAYS;

		$date = new DateTime();
		$date->modify( '+' . $estimated_days . ' day' );
		return $date->format( 'Y-m-d' );
	}

	/**
	 * @param array $translator_options
	 *
	 * @return WPML_TM_Jobs_Collection
	 */
	private function get_pending_jobs_for_translator( array $translator_options ) {
		$translator_id = $translator_options['translator_id'];
		$params        = new WPML_TM_Jobs_Search_Params();
		$params->set_status( array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ) );

		if ( false !== strpos( $translator_id, 'ts-' ) ) {
			$params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_REMOTE );
		} else {
			$params->set_scope( WPML_TM_Jobs_Search_Params::SCOPE_LOCAL );
			$params->set_translated_by( $translator_id );
		}

		if ( array_key_exists( 'to', $translator_options ) ) {
			$params->set_target_language( $translator_options['to'] );
		}

		return $this->jobs_repository->get( $params );
	}

	/**
	 * @param WPML_TM_Jobs_Collection $pending_jobs
	 *
	 * @return int[]
	 */
	private function get_pending_words_per_langs( WPML_TM_Jobs_Collection $pending_jobs ) {
		$words_per_langs = array();

		foreach ( $pending_jobs as $pending_job ) {
			if ( ! isset( $words_per_langs[ $pending_job->get_target_language() ] ) ) {
				$words_per_langs[ $pending_job->get_target_language() ] = 0;
			}

			$translatable_element = $this->translatable_element_provider->get_from_job( $pending_job );

			if ( $translatable_element ) {
				$words_per_langs[ $pending_job->get_target_language() ] += $translatable_element->get_words_count();
			}
		}

		return $words_per_langs;
	}

	/**
	 * @param array $basket
	 * @param int[] $words_per_langs
	 *
	 * @return int[]
	 */
	private function add_basket_words_per_langs( array $basket, array $words_per_langs ) {
		$element_types = array( 'post', 'string', 'package' );

		foreach ( $element_types as $element_type ) {
			if ( isset( $basket[ $element_type ] ) ) {
				foreach ( $basket[ $element_type ] as $element_id => $data ) {
					foreach ( $data['to_langs'] as $to_lang => $v ) {
						if ( ! isset( $words_per_langs[ $to_lang ] ) ) {
							$words_per_langs[ $to_lang ] = 0;
						}

						$translatable_element         = $this->translatable_element_provider->get_from_type( $element_type, $element_id );
						$words_per_langs[ $to_lang ] += $translatable_element->get_words_count();
					}
				}
			}
		}

		return $words_per_langs;
	}
}
