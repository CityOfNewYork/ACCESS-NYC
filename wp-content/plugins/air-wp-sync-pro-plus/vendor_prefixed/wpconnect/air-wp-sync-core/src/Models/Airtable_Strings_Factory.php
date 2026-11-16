<?php
/**
 * Airtable specific strings.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Models;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;

/**
 * Class Airtable_Strings_Factory
 */
class Airtable_Strings_Factory {

	/**
	 * Build Airtable Plugin_Strings instance.
	 *
	 * @return Plugin_Strings
	 */
	public static function build_plugin_strings() {
		return new Plugin_Strings(
			array(
				'admin.metaboxes.metabox_field_mapping.field_mapping_tooltip' => __( 'Add all the Airtable fields you want to synchronize and then select the corresponding data types where they will be imported into your post', 'air-wp-sync-pro-plus' ),
				'admin.metaboxes.metabox_import_infos.save_before_sync_instructions' => __( 'You will be able to sync your Airtable content once you have saved this connection.', 'air-wp-sync-pro-plus' ),
				'admin.metaboxes.metabox_sync_settings.sync_strategy_tooltip' => __( 'Select the method to synchronize your Airtable content with WordPress.<br><br><strong>Add:</strong> only adds new content.<br><br><strong>Add & Update:</strong> same + updates content from modified records.<br><br><strong>Add, Update & Delete:</strong> same + deletes content that is no longer in Airtable.', 'air-wp-sync-pro-plus' ),
				'admin.metaboxes.metabox_sync_settings.sync_strategy.manual_tooltip' => __( 'Disables automatic synchronization of your Airtable content. You can still do it manually by clicking on the \'Sync Now\' button in the side panel.', 'air-wp-sync-pro-plus' ),
				'admin.metaboxes.metabox_sync_settings.sync_strategy.recurring_tooltip' => __( 'Enables recurring synchronization of your Airtable content. Choose a frequency and it will take effect from the date and time the connection is updated.', 'air-wp-sync-pro-plus' ),
				'admin.metaboxes.metabox_sync_settings.sync_strategy.webhook_tooltip' => __( 'Enables instant synchronization of your content. Using an Airtable automation, you can choose your own trigger and the connection will be updated as soon as the webhook url below is called.', 'air-wp-sync-pro-plus' ),
				'admin.metaboxes.metabox_sync_settings.sync_strategy.webhook_instructions' => __( 'To create an automation and call a webhook, <a href="https://wpconnect.co/blog/create-airtable-automation" target="_blank">click here</a>', 'air-wp-sync-pro-plus' ),

				'admin.settings-page.cache_input_description' => __( 'WordPress caches your Airtable table structure for 15 minutes by default to improve performance. If changes made in Airtable aren’t showing right away, try clearing the cache or adjusting the duration above.', 'air-wp-sync-pro-plus' ),

				'admin.connection.database_not_available' => __( 'The base you have previously selected is not available anymore, please check your Airtable token access', 'air-wp-sync-pro-plus' ),

				'admin.menu.documentation_link'           => __( 'https://wpconnect.co/air-wp-sync-documentation', 'air-wp-sync-pro-plus' ),

				'post_module.connection.settings.delete_media_automatically_tooltip' => __( 'If this options is checked, when a post is no longer present in your Airtable, it will automatically delete media attached to the post we would have previously created from your WordPress during sync.', 'air-wp-sync-pro-plus' ),
			)
		);
	}
}
