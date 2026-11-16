<?php
/**
 * Display connection header.
 *
 * @package wpconnect/x-wp-sync-core
 */

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;

/**
 * Plugin’s admin header.
 *
 * @var Plugin_Context $plugin_context Plugin’s context.
 */
return function ( $plugin_context ) {
	?>

	<div class="airwpsync-admin-header">
		<h2>
			<a class="airwpsync-admin-header-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=airwpsync-connection' ) ); ?>">
				<img class="airwpsync-admin-header-logo" width="20" src="<?php echo esc_url( plugins_url( 'vendor_prefixed/wpconnect/x-wp-sync-core/src/Admin/assets/images/wpconnect-logo-white-v2.svg', $plugin_context->get_plugin_file() ) ); ?>"/>
				<span><?php echo esc_html( $plugin_context->get_plugin_name() ); ?></span>
			</a>
		</h2>

		<a class="airwpsync-admin-header-wpco" href="https://wpconnect.co/" target="_blank">
			<img width="105" height="14" src="<?php echo esc_url( plugins_url( 'vendor_prefixed/wpconnect/x-wp-sync-core/src/Admin/assets/images/logo-wpconnect-v3.svg', $plugin_context->get_plugin_file() ) ); ?>"/>
		</a>
	</div>
	<?php
};
