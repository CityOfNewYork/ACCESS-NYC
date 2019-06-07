<?php
/**
 * Utility functions for displaying form.
 *
 * @since      5.5
 *
 * @author     Sudar
 *
 * @package    BulkDelete\Ui
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Render filtering table header.
 *
 * @since 5.5
 */
function bd_render_filtering_table_header() {
?>
	<tr>
		<td colspan="2">
			<h4><?php _e( 'Choose your filtering options', 'bulk-delete' ); ?></h4>
		</td>
	</tr>
<?php
}

/**
 * Render "restrict by created date" dropdown.
 *
 * @since 5.5
 *
 * @param string $slug The slug to be used in field names.
 * @param string $item (optional) Item for which form is displayed. Default is 'posts'.
 */
function bd_render_restrict_settings( $slug, $item = 'posts' ) {
?>
	<tr>
		<td scope="row">
			<input name="smbd_<?php echo $slug; ?>_restrict" id="smbd_<?php echo $slug; ?>_restrict" value="true" type="checkbox">
		</td>
		<td>
			<label for="smbd_<?php echo $slug; ?>_restrict"><?php printf( __( 'Only restrict to %s which are ', 'bulk-delete' ), $item ); ?></label>
			<select name="smbd_<?php echo $slug; ?>_op" id="smbd_<?php echo $slug; ?>_op" disabled>
				<option value="before"><?php _e( 'older than', 'bulk-delete' );?></option>
				<option value="after"><?php _e( 'posted within last', 'bulk-delete' );?></option>
			</select>
			<input type="number" name="smbd_<?php echo $slug; ?>_days" id="smbd_<?php echo $slug; ?>_days" class="screen-per-page" disabled value="0" min="0"><?php _e( 'days', 'bulk-delete' );?>
		</td>
	</tr>
<?php
}

/**
 * Render "force delete" setting fields.
 *
 * @since 5.5
 *
 * @param string $slug The slug to be used in field names.
 */
function bd_render_delete_settings( $slug ) {
?>
	<tr>
		<td scope="row" colspan="2">
			<label><input name="smbd_<?php echo $slug; ?>_force_delete" value="false" type="radio" checked><?php _e( 'Move to Trash', 'bulk-delete' ); ?></label>
			<label><input name="smbd_<?php echo $slug; ?>_force_delete" value="true" type="radio"><?php _e( 'Delete permanently', 'bulk-delete' ); ?></label>
		</td>
	</tr>
<?php
}

/**
 * Render the "private post" setting fields.
 *
 * @since 5.5
 *
 * @param string $slug The slug to be used in field names.
 */
function bd_render_private_post_settings( $slug ) {
		?>
	<tr>
		<td scope="row" colspan="2">
			<label><input name="smbd_<?php echo $slug; ?>_private" value="false" type="radio" checked> <?php _e( 'Public posts', 'bulk-delete' ); ?></label>
			<label><input name="smbd_<?php echo $slug; ?>_private" value="true" type="radio"> <?php _e( 'Private Posts', 'bulk-delete' ); ?></label>
		</td>
	</tr>
		<?php
}

/**
 * Render the "limit" setting fields.
 *
 * @since 5.5
 *
 * @param string $slug The slug to be used in field names.
 * @param string $item (Optional) Item type. Possible values are 'posts', 'pages', 'users'
 */
function bd_render_limit_settings( $slug, $item = 'posts' ) {
?>
	<tr>
		<td scope="row">
			<input name="smbd_<?php echo $slug; ?>_limit" id="smbd_<?php echo $slug; ?>_limit" value="true" type="checkbox">
		</td>
		<td>
			<label for="smbd_<?php echo $slug; ?>_limit"><?php _e( 'Only delete first ', 'bulk-delete' ); ?></label>
			<input type="number" name="smbd_<?php echo $slug; ?>_limit_to" id="smbd_<?php echo $slug; ?>_limit_to" class="screen-per-page" disabled value="0" min="0"> <?php echo $item;?>.
			<?php printf( __( 'Use this option if there are more than 1000 %s and the script times out.', 'bulk-delete' ), $item ); ?>
		</td>
	</tr>
<?php
}

/**
 * Render cron setting fields.
 *
 * @since 5.5
 *
 * @param string $slug      The slug to be used in field names.
 * @param string $addon_url Url for the pro addon.
 */
function bd_render_cron_settings( $slug, $addon_url ) {
	$pro_class = 'bd-' . str_replace( '_', '-', $slug ) . '-pro';
?>
	<tr>
		<td scope="row" colspan="2">
			<label><input name="smbd_<?php echo $slug; ?>_cron" value="false" type="radio" checked="checked"> <?php _e( 'Delete now', 'bulk-delete' ); ?></label>
			<label><input name="smbd_<?php echo $slug; ?>_cron" value="true" type="radio" id="smbd_<?php echo $slug; ?>_cron" disabled > <?php _e( 'Schedule', 'bulk-delete' ); ?></label>
			<input name="smbd_<?php echo $slug; ?>_cron_start" id="smbd_<?php echo $slug; ?>_cron_start" value="now" type="text" disabled autocomplete="off"><?php _e( 'repeat ', 'bulk-delete' );?>
			<select name="smbd_<?php echo $slug; ?>_cron_freq" id="smbd_<?php echo $slug; ?>_cron_freq" disabled>
				<option value="-1"><?php _e( "Don't repeat", 'bulk-delete' ); ?></option>
<?php
	$schedules = wp_get_schedules();
	foreach ( $schedules as $key => $value ) {
?>
				<option value="<?php echo $key; ?>"><?php echo $value['display']; ?></option>
<?php } ?>
			</select>
			<span class="<?php echo sanitize_html_class( apply_filters( 'bd_pro_class', $pro_class, $slug ) ); ?>" style="color:red"><?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href="<?php echo $addon_url; ?>">Buy now</a></span>
		</td>
	</tr>

	<tr>
		<td scope="row" colspan="2">
			<?php _e( 'Enter time in <strong>Y-m-d H:i:s</strong> format or enter <strong>now</strong> to use current time', 'bulk-delete' );?>
		</td>
	</tr>
<?php
}

/**
 * Render the submit button.
 *
 * @since 5.5
 *
 * @param string $action The action attribute of the submit button.
 */
function bd_render_submit_button( $action ) {
?>
	<p class="submit">
		<button type="submit" name="bd_action" value="<?php echo esc_attr( $action ); ?>" class="button-primary"><?php _e( 'Bulk Delete ', 'bulk-delete' ); ?>&raquo;</button>
	</p>
<?php
}

/**
 * Get the list of post type objects that will be used in filters.
 *
 * @since 5.6.0
 *
 * @return \WP_Post_Type[] List of post type objects.
 */
function bd_get_post_types() {
	$custom_types = bd_get_custom_post_types();

	$builtin_types = bd_get_builtin_public_post_types();

	return array_merge( $builtin_types, $custom_types );
}

/**
 * Get the list of built-in public post types.
 *
 * @since 6.0.0
 *
 * @return \WP_Post_Type[] List of public built-in post types.
 */
function bd_get_builtin_public_post_types() {
	$builtin_types = array(
		'post' => get_post_type_object( 'post' ),
		'page' => get_post_type_object( 'page' ),
	);

	return $builtin_types;
}

/**
 * Get the list of custom post types.
 *
 * @since 6.0.0
 *
 * @return \WP_Post_Type[] List of custom post types.
 */
function bd_get_custom_post_types() {
	$custom_types = get_post_types( array( '_builtin' => false ), 'objects' );

	return $custom_types;
}

/**
 * Render Post type dropdown.
 *
 * @param string $field_slug Field slug.
 */
function bd_render_post_type_dropdown( $field_slug ) {
	$builtin_post_types = bd_get_builtin_public_post_types();
	$custom_post_types  = bd_get_custom_post_types();
	?>

	<tr>
		<td scope="row">
			<select class="enhanced-dropdown" name="smbd_<?php echo esc_attr( $field_slug ); ?>_post_type">
				<optgroup label="<?php esc_attr_e( 'Built-in Post Types', 'bulk-delete' ); ?>">
					<?php foreach ( $builtin_post_types as $type ) : ?>
						<option value="<?php echo esc_attr( $type->name ); ?>">
							<?php echo esc_html( $type->labels->singular_name . ' (' . $type->name . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>

				<optgroup label="<?php esc_attr_e( 'Custom Post Types', 'bulk-delete' ); ?>">
					<?php foreach ( $custom_post_types as $type ) : ?>
						<option value="<?php echo esc_attr( $type->name ); ?>">
							<?php echo esc_html( $type->labels->singular_name . ' (' . $type->name . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			</select>
		</td>
	</tr>
	<?php
}

/**
 * Render the post status filter.
 *
 * @since 5.6.0
 *
 * @param string $slug     The slug to be used in field names.
 * @param string $selected Default selected status.
 */
function bd_render_post_status_filter( $slug, $selected = 'publish' ) {
	$post_statuses = bd_get_post_statuses();

	foreach ( $post_statuses as $key => $value ) {
		?>
		<tr>
			<td>
				<label>
					<input name="smbd_<?php echo esc_attr( $slug ); ?>_post_status[]" type="checkbox"
							value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $selected ); ?>>

					<?php echo __( 'All', 'bulk-delete' ), ' ', esc_html( $value->label ), ' ', __( 'Posts', 'bulk-delete' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}
}

/**
 * Get the list of post statuses.
 *
 * This includes all custom post status, but excludes built-in private posts.
 *
 * @since 5.6.0
 *
 * @return array List of post status objects.
 */
function bd_get_post_statuses() {
	$post_statuses = get_post_stati( array(), 'object' );

	$exclude_post_statuses = bd_get_excluded_post_statuses();
	foreach ( $exclude_post_statuses as $key ) {
		unset( $post_statuses[ $key ] );
	}

	/**
	 * List of post statuses that are displayed in the post status filter.
	 *
	 * @since 5.6.0
	 *
	 * @param array $post_statuses List of post statuses.
	 */
	return apply_filters( 'bd_post_statuses', $post_statuses );
}

/**
 * Get the list of excluded post statuses.
 *
 * @since 6.0.0
 *
 * @return array List of excluded post statuses.
 */
function bd_get_excluded_post_statuses() {
	/**
	 * List of post statuses that should be excluded from post status filter.
	 *
	 * @since 5.6.0
	 *
	 * @param array $post_statuses List of post statuses to exclude.
	 */
	return apply_filters(
		'bd_excluded_post_statuses',
		array(
			'inherit',
			'trash',
			'auto-draft',
			'request-pending',
			'request-confirmed',
			'request-failed',
			'request-completed',
		)
	);
}

/**
 * Generate help tooltip and append it to existing markup.
 *
 * @param string $markup  Existing markup.
 * @param string $content Tooltip content.
 *
 * @return string Markup with tooltip markup appended to it.
 */
function bd_generate_help_tooltip( $markup, $content ) {
	if ( empty( $content ) ) {
		return $markup;
	}

	$tooltip = '<span alt="f223" class="bd-help dashicons dashicons-editor-help" title="' . $content . '"></span>';

	return $markup . $tooltip;
}
