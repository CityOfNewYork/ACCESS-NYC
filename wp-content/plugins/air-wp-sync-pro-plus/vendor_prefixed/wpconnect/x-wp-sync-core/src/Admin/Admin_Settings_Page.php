<?php
/**
 * Creates admin settings page.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Options_Settings;

/**
 * Admin Settings Page class
 */
class Admin_Settings_Page {

	/**
	 * Admin page slug
	 *
	 * @var string
	 */
	protected $page_slug = 'air-wp-sync-pro-plus-settings';

	/**
	 * Plugin’s context.
	 *
	 * @var Plugin_Context
	 */
	protected $plugin_context;

	/**
	 * Plugin's specific strings.
	 *
	 * @var Plugin_Context
	 */
	protected $plugin_strings;

	/**
	 * Plugin settings.
	 *
	 * @var Options_Settings
	 */
	protected $options;

	/**
	 * Constructor
	 *
	 * @param Plugin_Context   $plugin_context Plugin’s context.
	 * @param Plugin_Strings   $plugin_strings Plugin's specific strings.
	 * @param Options_Settings $options Plugin settings.
	 */
	public function __construct( $plugin_context, $plugin_strings, $options ) {
		$this->plugin_context = $plugin_context;
		$this->plugin_strings = $plugin_strings;
		$this->options        = $options;

		add_action( 'airwpsync/admin_submenu_pages_params', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
	}

	/**
	 * Add setting page in admin menu
	 *
	 * @param array $submenu_pages_params Plugin’s admin submenu pages params.
	 *
	 * @return array
	 */
	public function add_menu( $submenu_pages_params ) {
		$submenu_pages_params_with_settings = array();
		foreach ( $submenu_pages_params as $submenu_page_params ) {
			$submenu_pages_params_with_settings [] = $submenu_page_params;
			if ( 'post-new.php?post_type=airwpsync-connection' === $submenu_page_params[4] ) {
				$submenu_pages_params_with_settings [] = array(
					Admin::TOP_MENU_SLUG,
					__( 'Settings', 'air-wp-sync-pro-plus' ),
					__( 'Settings', 'air-wp-sync-pro-plus' ),
					apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
					$this->page_slug,
					array( $this, 'admin_page' ),
				);
			}
		}
		return $submenu_pages_params_with_settings;
	}


	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'air-wp-sync-pro-plus_page_air-wp-sync-pro-plus-settings' === $screen->id ) {
			wp_enqueue_script( 'air-wp-sync-pro-plus-settings', plugins_url( 'assets/js/settings-page.js', $this->plugin_context->get_plugin_file() ), array( 'jquery-ui-tooltip' ), $this->plugin_context->get_plugin_version(), false );
		}
	}

	/**
	 * Clear cache based on form submission
	 */
	protected function maybe_clear_cache() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'air-wp-sync-pro-plus-settings-form' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		if ( isset( $_POST['air-wp-sync-pro-plus-settings-clear-cache'] ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_airwpsync_tables_%'" );
			$this->display_message( __( 'Table cache cleared!', 'air-wp-sync-pro-plus' ), 'success' );
		}

		if ( isset( $_POST['air-wp-sync-pro-plus-settings-update'] ) ) {
			$cache_duration = ! empty( $_POST['cache_duration'] ) ? max( 2, (int) $_POST['cache_duration'] ) : 15;
			$this->options->set( 'cache_duration', $cache_duration );

			// Save options.
			$this->options->save();
			add_filter( 'airwpsync/admin/settings-saved', '__return_true' );
		}
	}

	/**
	 * Render admin page
	 */
	public function admin_page() {
		$this->maybe_clear_cache();
		do_action( 'airwpsync/admin/pre-render-settings', $this->options );
		if ( apply_filters( 'airwpsync/admin/settings-saved', false ) ) {
			$this->display_message( __( 'Settings saved!', 'air-wp-sync-pro-plus' ), 'success' );
		}
		$view = include __DIR__ . '/views/settings-page.php';
		$view( $this->plugin_strings, $this->options );
	}

	/**
	 * Display a WP notice
	 *
	 * @param string $message Message.
	 * @param string $type Message type (@see https://developer.wordpress.org/reference/hooks/admin_notices/#example).
	 */
	protected function display_message( $message, $type = 'info' ) {
		echo '<div class="notice notice-' . esc_attr( $type ) . '"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}
