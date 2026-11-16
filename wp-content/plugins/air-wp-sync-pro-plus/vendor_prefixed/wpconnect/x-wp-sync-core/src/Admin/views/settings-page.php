<?php
/**
 * Display the plugin settings page.
 *
 * @package wpconnect/x-wp-sync-core
 */

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Options_Settings;

/**
 * Plugin settings page.
 *
 * @param Plugin_Strings $plugin_strings Plugin's specific strings.
 * @param Options_Settings $options {
 *   @type number $cache_duration Cache duration.
 * }
 */
return function ( $plugin_strings, $options ) {
	$cache_duration = $options->get( 'cache_duration' );
	?>
<div class="wrap">

	<h2><?php esc_html_e( 'Settings', 'air-wp-sync-pro-plus' ); ?></h2>

	<form method="post">

		<?php wp_nonce_field( 'air-wp-sync-pro-plus-settings-form' ); ?>

		<table class="form-table">
			<?php
				do_action( 'airwpsync/admin/render-settings-form' );
			?>
			<tr valign="top">
				<th scope="row">
					<label for="cache_duration"><?php esc_html_e( 'Cache duration (minutes)', 'air-wp-sync-pro-plus' ); ?></label>
				</th>
				<td>
					<div>
						<input class="regular-text ltr"
								type="number"
								name="cache_duration"
								min="2"
								value="<?php echo esc_attr( $cache_duration ); ?>" />
						<p class="description"><?php echo esc_html( $plugin_strings->get_string( 'admin.settings-page.cache_input_description' ) ); ?></p>
					</div>
				</td>
			</tr>
		</table>
		<div id="poststuff"></div>

		<p class="submit">
			<input class="button button-primary"
					type="submit"
					name="air-wp-sync-pro-plus-settings-update"
					value="<?php esc_html_e( 'Update settings', 'air-wp-sync-pro-plus' ); ?>" />
			<input class="button button-secondary"
					type="submit"
					name="air-wp-sync-pro-plus-settings-clear-cache"
					value="<?php esc_html_e( 'Clear Cache', 'air-wp-sync-pro-plus' ); ?>"
			/>
		</p>
	</form>
</div>
	<?php
};
