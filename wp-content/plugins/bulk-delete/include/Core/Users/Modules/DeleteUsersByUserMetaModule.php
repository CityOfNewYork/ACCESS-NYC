<?php

namespace BulkWP\BulkDelete\Core\Users\Modules;

use BulkWP\BulkDelete\Core\Users\UsersModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Bulk Delete Users by User Meta.
 *
 * @since 5.5
 * @since 6.0.0 Renamed to DeleteUsersByUserMetaModule.
 */
class DeleteUsersByUserMetaModule extends UsersModule {
	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5
	 */
	protected function initialize() {
		$this->item_type     = 'users';
		$this->field_slug    = 'u_meta';
		$this->meta_box_slug = 'bd_users_by_meta';
		$this->action        = 'delete_users_by_meta';
		$this->cron_hook     = 'do-bulk-delete-users-by-meta';
		$this->scheduler_url = 'https://bulkwp.com/addons/scheduler-for-deleting-users-by-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-u-ma';
		$this->messages      = array(
			'box_label'  => __( 'By User Meta', 'bulk-delete' ),
			'scheduled'  => __( 'Users from with the selected user meta are scheduled for deletion.', 'bulk-delete' ),
			'cron_label' => __( 'Delete Users by User Meta', 'bulk-delete' ),
		);
	}

	/**
	 * Render delete users box.
	 *
	 * @since 5.5
	 */
	public function render() {
?>
		<!-- Users Start-->
		<h4><?php _e( 'Select the user meta from which you want to delete users', 'bulk-delete' ); ?></h4>

		<fieldset class="options">
			<table class="optiontable">
				<select name="smbd_u_meta_key" class="enhanced-dropdown">
					<?php
					$meta_keys = $this->get_unique_user_meta_keys();
					foreach ( $meta_keys as $meta_key ) {
						printf( '<option value="%s">%s</option>', esc_attr( $meta_key ), esc_html( $meta_key ) );
					}
					?>
				</select>

				<select name="smbd_u_meta_compare">
					<option value="=">Equals to</option>
					<option value="!=">Not Equals to</option>
					<option value=">">Greater than</option>
					<option value=">=">Greater than or equals to</option>
					<option value="<">Less than</option>
					<option value="<=">Less than or equals to</option>
					<option value="LIKE">Contains</option>
					<option value="NOT LIKE">Not Contains</option>
					<option value="STARTS WITH">Starts with</option>
					<option value="ENDS WITH">Ends with</option>
				</select>
				<input type="text" name="smbd_u_meta_value" id="smbd_u_meta_value" placeholder="<?php _e( 'Meta Value', 'bulk-delete' ); ?>">

			</table>

			<p>
				<?php _e( 'If you want to check for null values, then leave the value column blank', 'bulk-delete' ); ?>
			</p>

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
		<!-- Users end-->

		<?php
		$this->render_submit_button();
	}

	/**
	 * Process user input and create metabox options.
	 *
	 * @param array $request Request array.
	 * @param array $options User options.
	 *
	 * @return array User options.
	 */
	protected function convert_user_input_to_options( $request, $options ) {
		$options['meta_key']     = bd_array_get( $request, 'smbd_u_meta_key' );
		$options['meta_compare'] = bd_array_get( $request, 'smbd_u_meta_compare', '=' );
		$options['meta_value']   = bd_array_get( $request, 'smbd_u_meta_value' );

		switch ( strtolower( trim( $options['meta_compare'] ) ) ) {
			case 'starts with':
				$options['meta_compare'] = 'REGEXP';
				$options['meta_value']   = '^' . $options['meta_value'];
				break;
			case 'ends with':
				$options['meta_compare'] = 'REGEXP';
				$options['meta_value']   = $options['meta_value'] . '$';
				break;
		}

		return $options;
	}

	protected function build_query( $options ) {
		$query = array(
			'meta_query' => array(
				array(
					'key'     => $options['meta_key'],
					'value'   => $options['meta_value'],
					'compare' => $options['meta_compare'],
				),
			),
		);

		if ( $options['limit_to'] > 0 ) {
			$query['number'] = $options['limit_to'];
		}

		$date_query = $this->get_date_query( $options );

		if ( ! empty( $date_query ) ) {
			$query['date_query'] = $date_query;
		}

		return $query;
	}

	protected function append_to_js_array( $js_array ) {
		$js_array['validators'][ $this->action ] = 'noValidation';

		$js_array['pre_action_msg'][ $this->action ] = 'deleteUsersByMetaWarning';
		$js_array['msg']['deleteUsersByMetaWarning'] = __( 'Are you sure you want to delete all the users from the selected user meta?', 'bulk-delete' );

		$js_array['error_msg'][ $this->action ] = 'enterUserMetaValue';
		$js_array['msg']['enterUserMetaValue']  = __( 'Please enter the value for the user meta field based on which you want to delete users', 'bulk-delete' );

		return $js_array;
	}

	protected function get_success_message( $items_deleted ) {
		/* translators: 1 Number of users deleted */
		return _n( 'Deleted %d user with the selected user meta', 'Deleted %d users with the selected user meta', $items_deleted, 'bulk-delete' );
	}
}
