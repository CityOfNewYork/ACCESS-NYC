<?php

class WPML_ACF_Custom_Fields_Sync {

	const TR_JOB_FIELD_PATTERN = '/field-(\S+)-[0-9]/';

	public function register_hooks() {
		// make copy once synchornisation working (acfml-45)
		add_filter( 'wpml_custom_field_values', array($this, 'remove_cf_value_for_copy_once'), 10, 2);
		add_filter( 'wpml_tm_job_field_is_translatable', array( $this, 'adjust_is_translatable_for_field_in_translation_job' ), 10, 2 );

	}

	public function remove_cf_value_for_copy_once( $value, $context_data ) {
		/*
		 * when user starts translating post, acfml automatically creates custom fields with empty values
		 * WPML before running "copy once" checks if field doesn't exist - it is conflicting
		 * purpose of this code:
		 * if it is first copy-once synchronisation of acf field, remove value
		 */

		// check if this is copy once synchronisation
		if ( WPML_COPY_ONCE_CUSTOM_FIELD == $context_data['custom_fields_translation'] ) {
			// check if custom field is acf field
			$field = get_field_object($context_data['meta_key'], $context_data['post_id']);
			if ( false != $field ) {
				// check if value is array with empty strings
				if ( is_array($value) ) {
					$last_array = end( $value );
					if ( is_array( $last_array ) && end($value) === "" ) {
						$value = array();
					}

				}
			}
		}

		return $value;
	}

	/**
	 * WP filter hook to update $is_translatable to true when numeric values is being sent to translation and it is
	 * ACF field's value.
	 *
	 * @param bool  $is_translatable  if field should be displayed in Translation Editor
	 * @param array $job_translate    translation job details
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
	 * @param array $job_translate Translation Job data
	 *
	 * @return bool
	 */
	private function is_acf_field( $job_translate ) {
		return preg_match(self::TR_JOB_FIELD_PATTERN, $job_translate['field_type'], $matches) && (bool) acf_get_field( $matches[1] );
	}
}