<?php
/**
 * Display modules settings.
 *
 * @package wpconnect/x-wp-sync-core
 */

/**
 * Display modules settings.
 *
 * @param \Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Abstract_Module[] $modules A list of modules.
 * @param \WP_Post $post The post connection.
 */
return function ( $modules, $post ) {
	?>

<table class="form-table">
	<tr valign="top">
		<th scope="row">
			<label for="post_type"><?php esc_html_e( 'Import as', 'air-wp-sync-pro-plus' ); ?></label>
		</th>
		<td>
			<select class="regular-text ltr" name="airwpsync::module" x-model="config.module" x-init="config.module = config.module || $el.value;" @change="updateWordPressOptions();">
				<?php foreach ( $modules as $module ) : ?>
					<option value="<?php echo esc_attr( $module->get_slug() ); ?>"><?php echo esc_html( $module->get_name() ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
</table>
<hr>
	<?php foreach ( $modules as $module ) : ?>
	<template x-if="config.module === '<?php echo esc_attr( $module->get_slug() ); ?>'">
		<?php $module->render_settings( $post ); ?>
	</template>
<?php endforeach; ?>
	<?php
};
