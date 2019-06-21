<?php

namespace BulkWP\BulkDelete\Core\Users;

use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Encapsulates the Bulk Delete User Meta box Module Logic.
 * All Bulk Delete User Meta box Modules should extend this class.
 *
 * @see BaseModule
 * @since 5.5.2
 * @since 6.0.0 Renamed to UsersModule.
 */
abstract class UsersModule extends BaseModule {
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
	abstract protected function build_query( $options );

	protected function parse_common_filters( $request ) {
		$options = array();

		$options['login_restrict'] = bd_array_get_bool( $request, "smbd_{$this->field_slug}_login_restrict", false );
		$options['login_days']     = absint( bd_array_get( $request, "smbd_{$this->field_slug}_login_days", 0 ) );

		$options['registered_restrict'] = bd_array_get_bool( $request, "smbd_{$this->field_slug}_registered_restrict", false );
		$options['registered_date_op']  = bd_array_get( $request, 'smbd_' . $this->field_slug . '_op' );
		$options['registered_days']     = absint( bd_array_get( $request, "smbd_{$this->field_slug}_registered_days", 0 ) );

		$options['no_posts']            = bd_array_get_bool( $request, "smbd_{$this->field_slug}_no_posts", false );
		$options['no_posts_post_types'] = bd_array_get( $request, "smbd_{$this->field_slug}_no_post_post_types", array() );

		$options['reassign_user']    = bd_array_get_bool( $request, "smbd_{$this->field_slug}_post_reassign", false );
		$options['reassign_user_id'] = absint( bd_array_get( $request, "smbd_{$this->field_slug}_reassign_user_id", 0 ) );
		$options['limit_to']         = absint( bd_array_get( $request, "smbd_{$this->field_slug}_limit_to", 0 ) );

		return $options;
	}

	protected function do_delete( $options ) {
		$query = $this->build_query( $options );

		if ( empty( $query ) ) {
			// Short circuit deletion, if nothing needs to be deleted.
			return 0;
		}

		$query = $this->exclude_users_from_deletion( $query );
		$query = $this->exclude_current_user( $query );

		return $this->delete_users_from_query( $query, $options );
	}

	/**
	 * Query and Delete users.
	 *
	 * @since  5.5.2
	 * @access protected
	 *
	 * @param array $query   Options to query users.
	 * @param array $options Delete options.
	 *
	 * @return int Number of users who were deleted.
	 */
	protected function delete_users_from_query( $query, $options ) {
		$count = 0;
		$users = $this->query_users( $query );

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		foreach ( $users as $user ) {
			if ( ! $this->can_delete_by_logged_date( $options, $user ) ) {
				continue;
			}

			if ( ! $this->can_delete_by_post_count( $options, $user ) ) {
				continue;
			}

			/**
			 * Can a user be deleted.
			 *
			 * @since 6.0.0
			 *
			 * @param bool                                             Can Delete the User. (Default true)
			 * @param \WP_User                                $user    User Object of the user who is about to be deleted.
			 * @param array                                   $options Delete options.
			 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $this    Module that is triggering deletion.
			 */
			if ( ! apply_filters( 'bd_can_delete_user', true, $user, $options, $this ) ) {
				continue;
			}

			if ( isset( $options['reassign_user'] ) && $options['reassign_user'] ) {
				$deleted = wp_delete_user( $user->ID, $options['reassign_user_id'] );
			} else {
				$deleted = wp_delete_user( $user->ID );
			}

			if ( $deleted ) {
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Query users using options.
	 *
	 * @param array $options Query options.
	 *
	 * @return \WP_User[] List of users.
	 */
	protected function query_users( $options ) {
		$defaults = array(
			'count_total' => false,
		);

		$options = wp_parse_args( $options, $defaults );

		$wp_user_query = new \WP_User_Query( $options );

		/**
		 * This action before the query happens.
		 *
		 * @since 6.0.0
		 *
		 * @param \WP_User_Query $wp_user_query Query object.
		 */
		do_action( 'bd_before_query', $wp_user_query );

		$users = (array) $wp_user_query->get_results();

		/**
		 * This action runs after the query happens.
		 *
		 * @since 6.0.0
		 *
		 * @param \WP_User_Query $wp_user_query Query object.
		 */
		do_action( 'bd_after_query', $wp_user_query );

		return $users;
	}

	/**
	 * Can the user be deleted based on the 'post count' option?
	 *
	 * This doesn't work well in batches.
	 *
	 * @link https://github.com/sudar/bulk-delete/issues/511 Github issue.
	 * @since  5.5.2
	 * @access protected
	 *
	 * @param array    $delete_options Delete Options.
	 * @param \WP_User $user           User object that needs to be deleted.
	 *
	 * @return bool True if the user can be deleted, false otherwise.
	 */
	protected function can_delete_by_post_count( $delete_options, $user ) {
		return ! (
			$delete_options['no_posts'] &&
			count_user_posts( $user->ID, $delete_options['no_posts_post_types'] ) > 0
		);
	}

	/**
	 * Get the date query part for WP_User_Query.
	 *
	 * Date query corresponds to user registered date.
	 *
	 * @since 6.0.0
	 *
	 * @param array $options Delete options.
	 *
	 * @return array Date Query.
	 */
	protected function get_date_query( $options ) {
		if ( ! $options['registered_restrict'] ) {
			return array();
		}

		if ( $options['registered_days'] <= 0 ) {
			return array();
		}

		if ( ! isset( $options['registered_date_op'] ) ) {
			return array(
				'before' => $options['registered_days'] . ' days ago',
			);
		}

		if ( 'before' === $options['registered_date_op'] || 'after' === $options['registered_date_op'] ) {
			return array(
				$options['registered_date_op'] => $options['registered_days'] . ' days ago',
			);
		}

		return array();
	}

	/**
	 * Can the user be deleted based on the 'logged in date' option?
	 *
	 * This doesn't work well in batches.
	 *
	 * @link https://github.com/sudar/bulk-delete/issues/511 Github issue.
	 * @since  5.5.2
	 * @access protected
	 *
	 * @param array    $delete_options Delete Options.
	 * @param \WP_User $user           User object that needs to be deleted.
	 *
	 * @return bool True if the user can be deleted, false otherwise.
	 */
	protected function can_delete_by_logged_date( $delete_options, $user ) {
		if ( $delete_options['login_restrict'] ) {
			$login_days = $delete_options['login_days'];
			$last_login = bd_get_last_login( $user->ID );

			if ( null !== $last_login ) {
				// we have a logged-in entry for the user in simple login log plugin.
				if ( strtotime( $last_login ) > strtotime( '-' . $login_days . 'days' ) ) {
					return false;
				}
			} else {
				// we don't have a logged-in entry for the user in simple login log plugin.
				if ( $login_days > 0 ) {
					// non-zero value for login date. So don't delete this user.
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Render User Login restrict settings.
	 *
	 * @since 5.5
	 */
	protected function render_user_login_restrict_settings() {
		?>
		<tr>
			<td scope="row" colspan="2">
				<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_restrict">
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_restrict" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_restrict" value="true" type="checkbox">
					<?php _e( 'Restrict to users who are registered in the site ', 'bulk-delete' ); ?>
				</label>
				<select name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_op" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_op" disabled>
					<option value="before"><?php _e( 'for at least', 'bulk-delete' ); ?></option>
					<option value="after"><?php _e( 'in the last', 'bulk-delete' ); ?></option>
				</select>
				<input type="number" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_days" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_days" class="screen-per-page" disabled value="0" min="0"><?php _e( ' days.', 'bulk-delete' ); ?>
			</td>
		</tr>
		<tr>
			<td scope="row" colspan="2">
				<label>
					<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_login_restrict" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_login_restrict"
							value="true" type="checkbox" <?php disabled( false, bd_is_simple_login_log_present() ); ?>>
					<?php _e( 'Restrict to users who have not logged in the last ', 'bulk-delete' ); ?>
				</label>
				<input type="number" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_login_days" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_login_days" class="screen-per-page" value="0" min="0" disabled> <?php _e( 'days', 'bulk-delete' ); ?>.

				<?php if ( ! bd_is_simple_login_log_present() ) : ?>
					<span style = "color:red">
						<?php _e( 'Need the free "Simple Login Log" Plugin', 'bulk-delete' ); ?> <a href = "https://wordpress.org/plugins/simple-login-log/">Install now</a>
					</span>
				<?php endif; ?>
			</td>
		</tr>

		<?php if ( bd_is_simple_login_log_present() ) : ?>
			<tr>
				<td scope="row" colspan="2">
					<?php _e( 'Enter "0 days" to delete users who have never logged in after the "Simple Login Log" plugin has been installed.', 'bulk-delete' ); ?>
			</tr>
		<?php endif; ?>
<?php
	}

	/**
	 * Render delete user with no posts settings.
	 *
	 * @since 5.5
	 */
	protected function render_user_with_no_posts_settings() {
	?>
		<tr>
			<td scope="row" colspan="2">
				<input type="checkbox" value="true"
						name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_no_posts"
						id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_no_posts" class="user_restrict_to_no_posts_filter">
				<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_no_posts">
					<?php _e( "Restrict to users who don't have any posts.", 'bulk-delete' ); ?>
				</label>
			</td>
		</tr>

		<tr class="user_restrict_to_no_posts_filter_items visually-hidden">
			<td scope="row" colspan="2">
				<table class="filter-items">
					<tr>
						<td scope="row">
							<?php _e( 'Select the post types. By default all post types are considered.', 'bulk-delete' ); ?>
						</td>
					</tr>

					<?php $this->render_post_type_checkboxes( "smbd_{$this->field_slug}_no_post_post_types" ); ?>
				</table>
			</td>
		</tr>

	<?php
	}

	/**
	 * Get unique user meta keys.
	 *
	 * @since 5.5
	 *
	 * @return array List of unique meta keys.
	 */
	protected function get_unique_user_meta_keys() {
		global $wpdb;

		return $wpdb->get_col( "SELECT DISTINCT(meta_key) FROM {$wpdb->prefix}usermeta ORDER BY meta_key" );
	}

	/**
	 * Exclude current user from being deleted.
	 *
	 * @param array $query WP_User_Query args.
	 *
	 * @return array Modified query args.
	 */
	protected function exclude_current_user( $query ) {
		$current_user_id = get_current_user_id();

		if ( $current_user_id <= 0 ) {
			return $query;
		}

		if ( isset( $query['exclude'] ) ) {
			$query['exclude'] = array_merge( $query['exclude'], array( $current_user_id ) );
		} else {
			$query['exclude'] = array( $current_user_id );
		}

		return $query;
	}

	/**
	 * Exclude users from deletion.
	 *
	 * @since 6.0.0
	 *
	 * @param array $query Pre-built query.
	 *
	 * @return array Modified query.
	 */
	protected function exclude_users_from_deletion( array $query ) {
		/**
		 * Filter the list of user ids that will be excluded from deletion.
		 *
		 * @since 6.0.0
		 *
		 * @param array $excluded_ids User IDs to be excluded during deletion.
		 */
		$excluded_user_ids = apply_filters( 'bd_excluded_user_ids', array() );

		if ( is_array( $excluded_user_ids ) && ! empty( $excluded_user_ids ) ) {
			if ( isset( $query['exclude'] ) ) {
				$query['exclude'] = array_merge( $query['exclude'], $excluded_user_ids );
			} else {
				$query['exclude'] = $excluded_user_ids;
			}
		}

		return $query;
	}
}
