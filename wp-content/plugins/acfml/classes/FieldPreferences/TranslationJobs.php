<?php

namespace ACFML\FieldPreferences;

class TranslationJobs implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const TR_JOB_FIELD_PATTERN = '/^field-(\S+)-[0-9]+$/';

	public function add_hooks() {
		add_filter( 'wpml_tm_job_field_is_translatable', [ $this, 'adjust_is_translatable_for_field_in_translation_job' ], 10, 2 );
	}

	/**
	 * WP filter hook to update $is_translatable to true when numeric values is being sent to translation and it is
	 * ACF field's value.
	 *
	 * @param bool  $is_translatable  if field should be displayed in Translation Editor.
	 * @param array $job_translate    translation job details.
	 *
	 * @return bool
	 */
	public function adjust_is_translatable_for_field_in_translation_job( $is_translatable, $job_translate ) {
		/*
		 * Numeric fields are set as not translatable in translation jobs
		 * but with ACF you can create fields with numeric value which actually you would like
		 * to translate. This filter is to check if field comes from ACF and then set it as translatable
		 */
		if ( ! $is_translatable && isset( $job_translate['field_type'] ) ) {
			if ( $this->is_acf_field( $job_translate ) ) {
				$is_translatable = true;
			}
		}

		return $is_translatable;
	}

	/**
	 * @param array $job_translate Translation Job data.
	 *
	 * @return bool
	 */
	private function is_acf_field( $job_translate ) {
		return preg_match( self::TR_JOB_FIELD_PATTERN, $job_translate['field_type'], $matches ) && (bool) acf_get_field( $matches[1] );
	}
}
