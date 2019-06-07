<?php
namespace BulkWP\BulkDelete\Core\Metas\Modules;

use BulkWP\BulkDelete\Core\Metas\MetasModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Delete User Meta.
 *
 * @since 6.0.0
 */
class DeleteUserMetaModule extends MetasModule {
	protected function initialize() {
		$this->field_slug    = 'um'; // Ideally it should be `meta_user`. But we are keeping it as um for backward compatibility.
		$this->meta_box_slug = 'bd-user-meta';
		$this->action        = 'delete_user_meta';
		$this->cron_hook     = 'do-bulk-delete-user-meta';
		$this->scheduler_url = 'https://bulkwp.com/addons/bulk-delete-user-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-m-u';
		$this->messages      = array(
			'box_label'  => __( 'Bulk Delete User Meta', 'bulk-delete' ),
			'scheduled'  => __( 'User meta fields from the users with the selected criteria are scheduled for deletion.', 'bulk-delete' ),
			'cron_label' => __( 'Delete User Meta`', 'bulk-delete' ),
		);
	}

	/**
	 * Render the Modules.
	 */
	public function render() {
		?>
		<!-- User Meta box start-->
		<fieldset class="options">
			<h4><?php _e( 'Select the user role whose user meta fields you want to delete', 'bulk-delete' ); ?></h4>

			<table class="optiontable">
				<?php $this->render_user_role_dropdown(); ?>
			</table>

			<h4><?php _e( 'Choose your user meta field settings', 'bulk-delete' ); ?></h4>
			<table class="optiontable">
				<tr>
					<td>
						<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value" value="false" type="radio" checked>
						<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value"><?php echo __( 'Delete based on user meta key name only', 'bulk-delete' ); ?></label>
					</td>
				</tr>

				<tr>
					<td>
						<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value"
								value="true" type="radio" disabled>
						<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_use_value"><?php echo __( 'Delete based on user meta key name and value', 'bulk-delete' ); ?></label>
						<span class="bd-um-pro" style="color:red; vertical-align: middle;">
							<?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?>
							<a href="https://bulkwp.com/addons/bulk-delete-user-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-m-u" target="_blank">Buy now</a>
						</span>
					</td>
				</tr>

				<tr>
					<td>
						<label for="smbd_<?php echo esc_attr( $this->field_slug ); ?>_key"><?php _e( 'User Meta Key ', 'bulk-delete' ); ?></label>
						<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_key" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_key" placeholder="<?php _e( 'Meta Key', 'bulk-delete' ); ?>">
					</td>
				</tr>
			</table>
		<?php
		/**
		 * Add more fields to the delete user meta field form.
		 * This hook can be used to add more fields to the delete user meta field form.
		 *
		 * @since 5.4
		 */
		do_action( 'bd_delete_user_meta_form' );
		?>
			<table class="optiontable">
				<tr>
					<td colspan="2">
						<h4><?php _e( 'Choose your deletion options', 'bulk-delete' ); ?></h4>
					</td>
				</tr>

				<?php $this->render_limit_settings(); ?>
				<?php $this->render_cron_settings(); ?>
			</table>
		</fieldset>

		<?php $this->render_submit_button(); ?>
		<!-- User Meta box end-->
		<?php
	}

	protected function convert_user_input_to_options( $request, $options ) {
		$options['selected_roles'] = esc_sql( bd_array_get( $request, 'smbd_' . $this->field_slug . '_roles' ) );
		$options['use_value']      = sanitize_text_field( bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_use_value', false ) );
		$options['meta_key']       = esc_sql( bd_array_get( $request, 'smbd_' . $this->field_slug . '_key', '' ) );
		$options['meta_value']     = esc_sql( bd_array_get( $request, 'smbd_' . $this->field_slug . '_value', '' ) );

		/**
		 * Delete user-meta delete options filter.
		 *
		 * This filter is for processing filtering options for deleting user meta.
		 *
		 * @since 5.4
		 */
		return apply_filters( 'bd_delete_user_meta_options', $options, $request );
	}

	protected function do_delete( $options ) {
		$count     = 0;
		$meta_key  = $options['meta_key'];
		$use_value = $options['use_value'];
		$limit_to  = $options['limit_to'];

		$args = array(
			'role__in' => $options['selected_roles'],
		);

		if ( $limit_to > 0 ) {
			$args['number'] = $limit_to;
		}

		if ( $use_value ) {
			$args['meta_query'] = apply_filters( 'bd_delete_user_meta_query', array(), $options );
		} else {
			$args['meta_key'] = $meta_key;
		}

		$users = get_users( $args );

		foreach ( $users as $user ) {
			if ( $use_value ) {
				if ( delete_user_meta( $user->ID, $meta_key, $options['meta_value'] ) ) {
					$count++;
				}
			} else {
				if ( delete_user_meta( $user->ID, $meta_key ) ) {
					$count++;
				}
			}
		}

		return $count;
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators']['delete_user_meta'] = 'noValidation';

		$js_array['pre_action_msg']['delete_user_meta'] = 'deleteUMWarning';
		$js_array['msg']['deleteUMWarning']             = __( 'Are you sure you want to delete all the user meta fields that match the selected filters?', 'bulk-delete' );

		return $js_array;
	}

	protected function get_success_message( $items_deleted ) {
		/* translators: 1 Number of posts deleted */
		return _n( 'Deleted user meta field from %d user', 'Deleted user meta field from %d users', $items_deleted, 'bulk-delete' );
	}
}
