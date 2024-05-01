<?php

use ACFML\FieldState;

class WPML_ACF_Custom_Fields_Sync implements \IWPML_Backend_Action {

	/**
	 * @var FieldState
	 */
	private $field_state;

	public function __construct( FieldState $field_state ) {
		$this->field_state = $field_state;
	}

	/**
	 * Registers hooks related to custom fields synchronisation.
	 */
	public function add_hooks() {
		// make copy once synchronisation working (acfml-155).
		add_filter( 'acf/update_value', [ $this, 'clean_empty_values_for_copy_once_field' ], 10, 3 );
	}

	/**
	 * Change empty custom field value into null when field is set to "Copy once".
	 *
	 * On the very beginning of the post creation process, ACF saves values into postmeta
	 * even though fields are empty. This makes "copy once" option not working:
	 * translated posts always has some value in this field and copying doesn't start.
	 * However, if this value is strictly equal to null, ACF deletes postmeta
	 * instead of saving empty value. Copy once sees there is no value and is copying
	 * value from original post.
	 *
	 * @see \acf_update_value()
	 *
	 * @param mixed      $value          Filtered custom field value.
	 * @param int|string $post_id        ID of the post or option page.
	 * @param array      $field          ACF field data.
	 *
	 * @return mixed|null Filtered value.
	 */
	public function clean_empty_values_for_copy_once_field( $value, $post_id, $field ) {
		if ( '' === $value
			&& ! $this->value_has_been_emptied( $field )
			&& isset( $field['wpml_cf_preferences'] )
			&& WPML_COPY_ONCE_CUSTOM_FIELD === $field['wpml_cf_preferences']
			&& ! $this->isFieldType( $field, 'group' )
		) {
			$value = null;
		}
		return $value;
	}

	private function value_has_been_emptied( $field ) {
		$state_before = $this->field_state->getStateBefore();
		return ! empty( $state_before[ $field['name'] ] );
	}

	/**
	 * Check if field is of given type.
	 *
	 * @param array  $field The ACF field.
	 * @param string $type  Field type.
	 *
	 * @return bool
	 */
	private function isFieldType( $field, $type ) {
		return isset( $field['type'] ) && $type === $field['type'];
	}
}
