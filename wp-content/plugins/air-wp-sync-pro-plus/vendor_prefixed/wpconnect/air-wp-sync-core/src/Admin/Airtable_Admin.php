<?php
/**
 * Manages specific Airtable metaboxes and API endpoints.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Admin\Metaboxes\Metabox_Global_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers\Airtable_Filters;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Services;

/**
 * Class Airtable_Admin
 */
class Airtable_Admin {

	/**
	 * Plugin’s context.
	 *
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Constructor
	 *
	 * @param Plugin_Context   $plugin_context Plugin’s context.
	 * @param callable         $api_client_class_factory The API client class factory.
	 * @param Airtable_Filters $filters Airtable_Filters class instance.
	 */
	public function __construct( $plugin_context, $api_client_class_factory, $filters ) {
		$this->plugin_context = $plugin_context;
		$this->add_meta_boxes();

		new Airtable_Admin_Connection_API( $api_client_class_factory, $filters );

		add_action( 'airwpsync/admin/admin-connection/register-styles-scripts', array( $this, 'admin_connection_register_extra_styles_and_scripts' ) );
	}

	/**
	 * Add metaboxes for importer post type
	 */
	public function add_meta_boxes() {
		new Metabox_Global_Settings( Services::get_instance()->get( 'filters' ) );
	}

	/**
	 * Register filters script and Air WP Sync UI styles.
	 *
	 * @return void
	 */
	public function admin_connection_register_extra_styles_and_scripts() {
		wp_enqueue_script( 'air-wp-sync-pro-plus-admin-filters', plugins_url( 'assets/js/filters/main.js', $this->plugin_context->get_plugin_file() ), array( 'wp-i18n' ), $this->plugin_context->get_plugin_version(), false );
		wp_set_script_translations( 'air-wp-sync-pro-plus-admin-filters', 'air-wp-sync-pro-plus', $this->plugin_context->get_plugin_file() . 'languages/' );

		wp_enqueue_style( 'air-wp-sync-pro-plus-ui', plugins_url( 'assets/js/air-wp-sync-ui/library/index.css', $this->plugin_context->get_plugin_file() ), false, $this->plugin_context->get_plugin_version() );
		// .airwpsync-ui class is required by 'air-wp-sync-ui' style.
		add_filter(
			'admin_body_class',
			function ( $body_class ) {
				$body_class .= ' airwpsync-ui';
				return $body_class;
			}
		);
	}
}
