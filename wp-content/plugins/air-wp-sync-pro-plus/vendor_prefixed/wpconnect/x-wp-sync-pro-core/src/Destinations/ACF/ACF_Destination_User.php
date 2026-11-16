<?php
/**
 * Manages import as ACF field on users.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\ACF;

/**
 * ACF_Destination_User class.
 */
class ACF_Destination_User extends Abstract_ACF_Destination {

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $module = 'user';

	/**
	 * Update user's ACF field.
	 *
	 * @param string $key Field key.
	 * @param mixed  $value Field valye.
	 * @param int    $destination_id WordPress destination object id.
	 *
	 * @return void
	 */
	public function update_field( $key, $value, $destination_id ) {
		update_field( $key, $value, 'user_' . $destination_id );
	}

	/**
	 * Try to find related user fields if $filters is empty.
	 *
	 * @param array $filters Filters passed to `acf_get_field_groups`.
	 *
	 * @return array
	 */
	protected function get_acf_fields( $filters = array() ) {
		if ( ! empty( $filters ) ) {
			parent::get_acf_fields( $filters );
		}

		$user_form_all      = parent::get_acf_fields( array( 'user_form' => 'all' ) );
		$user_form_add_edit = parent::get_acf_fields( array( 'user_form' => 'add/edit' ) );
		$user_form_add      = parent::get_acf_fields( array( 'user_form' => 'add' ) );
		$user_form_register = parent::get_acf_fields( array( 'user_form' => 'register' ) );

		$all_fields = array_merge(
			$user_form_all,
			$user_form_add_edit,
			$user_form_add,
			$user_form_register
		);

		$unique_fields      = array();
		$unique_fields_keys = array();

		foreach ( $all_fields as $field ) {
			if ( ! in_array( $field['key'], $unique_fields_keys, true ) ) {
				$unique_fields_keys[] = $field['key'];
				$unique_fields[]      = $field;
			}
		}

		return $unique_fields;
	}
}
