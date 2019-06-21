<?php

class WPML_ACF_Custom_Fields_Sync {
	public function register_hooks() {
		// make copy once synchornisation working (acfml-45)
		add_filter( 'wpml_custom_field_values', array($this, 'remove_cf_value_for_copy_once'), 10, 2);

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
}