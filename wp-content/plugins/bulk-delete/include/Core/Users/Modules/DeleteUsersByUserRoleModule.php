<?php

namespace BulkWP\BulkDelete\Core\Users\Modules;

use BulkWP\BulkDelete\Core\Users\UsersModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Users by User Role Module.
 *
 * @since 5.5
 * @since 6.0.0 Renamed to DeleteUsersByUserRoleModule
 */
class DeleteUsersByUserRoleModule extends UsersModule {
	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5
	 */
	protected function initialize() {
		$this->item_type     = 'users';
		$this->field_slug    = 'u_role';
		$this->meta_box_slug = 'bd_users_by_role';
		$this->action        = 'delete_users_by_role';
		$this->cron_hook     = 'do-bulk-delete-users-by-role';
		$this->scheduler_url = 'https://bulkwp.com/addons/scheduler-for-deleting-users-by-role/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-u-ur';
		$this->messages      = array(
			'box_label'  => __( 'By User Role', 'bulk-delete' ),
			'scheduled'  => __( 'Users from the selected user role are scheduled for deletion.', 'bulk-delete' ),
			'cron_label' => __( 'Delete Users by User Role', 'bulk-delete' ),
		);
	}

	/**
	 * Render delete users box.
	 *
	 * @since 5.5
	 */
	public function render() {
		?>
		<h4><?php _e( 'Select the user roles from which you want to delete users', 'bulk-delete' ); ?></h4>

		<fieldset class="options">
			<table class="optiontable">
				<?php $this->render_user_role_dropdown( true ); ?>
			</table>

			<table class="optiontable">
				<?php
					$this->render_filtering_table_header();
					$this->render_user_login_restrict_settings();
					$this->render_user_with_no_posts_settings();
					$this->render_limit_settings();
					$this->render_post_reassign_settings();
					$this->render_cron_settings();
				?>
			</table>
		</fieldset>
		<?php
		$this->render_submit_button();
	}

	protected function convert_user_input_to_options( $request, $options ) {
		$selected_roles = bd_array_get( $request, 'smbd_' . $this->field_slug . '_roles', array() );

		$key = array_search( 'none', $selected_roles, true );
		if ( false !== $key ) {
			unset( $selected_roles[ $key ] );
			$options['delete_users_with_no_role'] = true;
		}

		$options['selected_roles'] = $selected_roles;

		return $options;
	}

	/**
	 * Handle both users with roles and without roles.
	 *
	 * {@inheritdoc}
	 *
	 * @param array $options Array of Delete options.
	 *
	 * @return int Number of items that were deleted.
	 */
	protected function do_delete( $options ) {
		$users_with_roles_deleted = parent::do_delete( $options );

		if ( ! isset( $options['delete_users_with_no_role'] ) ) {
			return $users_with_roles_deleted;
		}

		return $users_with_roles_deleted + $this->delete_users_with_no_roles( $options );
	}

	/**
	 * Delete users with no roles.
	 *
	 * @since 6.0.0
	 *
	 * @param array $options User options.
	 *
	 * @return int Number of users that were deleted.
	 */
	protected function delete_users_with_no_roles( $options ) {
		$query = $this->build_query_for_deleting_users_with_no_roles( $options );

		if ( empty( $query ) ) {
			// Short circuit deletion, if nothing needs to be deleted.
			return 0;
		}

		$query = $this->exclude_users_from_deletion( $query );
		$query = $this->exclude_current_user( $query );

		return $this->delete_users_from_query( $query, $options );
	}

	/**
	 * Build query params for WP_User_Query by using delete options for deleting users with no roles.
	 *
	 * Return an empty query array to short-circuit deletion.
	 *
	 * @since 6.0.0
	 *
	 * @param array $options Delete options.
	 *
	 * @return array Query.
	 */
	protected function build_query_for_deleting_users_with_no_roles( $options ) {
		// Users with no role is not selected.
		if ( ! isset( $options['delete_users_with_no_role'] ) || ! $options['delete_users_with_no_role'] ) {
			return array();
		}

		$roles      = get_editable_roles();
		$role_names = array_keys( $roles );

		$query = array(
			'role__not_in' => $role_names,
			'number'       => $options['limit_to'],
		);

		$date_query = $this->get_date_query( $options );

		if ( ! empty( $date_query ) ) {
			$query['date_query'] = $date_query;
		}

		return $query;
	}

	/**
	 * Build query params for WP_User_Query by using delete options.
	 *
	 * Return an empty query array to short-circuit deletion.
	 *
	 * @since 6.0.0
	 *
	 * @param array $options Delete options.
	 *
	 * @return array Query.
	 */
	protected function build_query( $options ) {
		// No role is selected.
		if ( empty( $options['selected_roles'] ) ) {
			return array();
		}

		$query = array(
			'role__in' => $options['selected_roles'],
			'number'   => $options['limit_to'],
		);

		$date_query = $this->get_date_query( $options );

		if ( ! empty( $date_query ) ) {
			$query['date_query'] = $date_query;
		}

		return $query;
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'validateUserRole';

		$js_array['pre_action_msg'][ $this->action ] = 'deleteUsersWarning';
		$js_array['msg']['deleteUsersWarning']       = __( 'Are you sure you want to delete all the users from the selected user role?', 'bulk-delete' );

		$js_array['error_msg'][ $this->action ] = 'selectOneUserRole';
		$js_array['msg']['selectOneUserRole']   = __( 'Select at least one user role from which users should be deleted', 'bulk-delete' );

		return $js_array;
	}

	protected function get_success_message( $items_deleted ) {
		/* translators: 1 Number of users deleted */
		return _n( 'Deleted %d user from the selected roles', 'Deleted %d users from the selected roles', $items_deleted, 'bulk-delete' );
	}
}
