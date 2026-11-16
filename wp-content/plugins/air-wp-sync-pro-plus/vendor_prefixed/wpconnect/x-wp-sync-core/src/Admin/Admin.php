<?php
/**
 * Manages admin pages registration.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings\Options_Settings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Site_Health;

/**
 * Class Admin
 */
class Admin {

	const TOP_MENU_SLUG = 'air-wp-sync-pro-plus';

	/**
	 * Plugin settings.
	 *
	 * @var Options_Settings
	 */
	protected $options;

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
	 * Constructor
	 *
	 * @param Options_Settings $options Plugin settings.
	 * @param Plugin_Context   $plugin_context Plugin’s context.
	 * @param Plugin_Strings   $plugin_strings Plugin's specific strings.
	 */
	public function __construct( $options, $plugin_context, $plugin_strings ) {
		$this->options        = $options;
		$this->plugin_context = $plugin_context;
		$this->plugin_strings = $plugin_strings;

		add_action( 'admin_menu', array( $this, 'add_menu' ), 100 );
		add_action( 'in_admin_header', array( $this, 'in_admin_header' ) );
		add_filter( 'airwpsync/should_display_admin_header', array( $this, 'should_display_admin_header' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
		add_action( 'admin_notices', array( $this, 'add_notices' ) );
		add_action( 'wp_ajax_air_wp_sync_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );

		add_filter( 'plugin_action_links_' . $this->plugin_context->get_plugin_basename(), array( $this, 'plugin_action_links' ) );

		new Admin_Connection_Api();
		new Admin_Connections_List();
		new Admin_Connection( $plugin_context, $plugin_strings );
		if ( apply_filters( 'airwpsync/admin/should_display_settings_page', false ) ) {
			new Admin_Settings_Page( $plugin_context, $plugin_strings, $options );
		}
	}

	/**
	 * Add menu
	 */
	public function add_menu() {
		add_menu_page(
			$this->plugin_context->get_admin_menu_name(),
			$this->plugin_context->get_admin_menu_name(),
			apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
			self::TOP_MENU_SLUG,
			array( $this, 'redirect_top_menu' ),
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODMuOTkgMTE4Ij48cGF0aCBkPSJNNTAuNjYgOTAuMjQgMjUuMDEgNzUuNThjLS42Mi0uMzYtLjk5LTEuMDEtLjk5LTEuNzJWNDQuMTNjMC0uNzEuMzgtMS4zNi45OS0xLjcybDI2Ljk4LTE1LjQ0Uzk3LjEgNTMuODMgMTMxLjA2LjkzYy0zOC43MyAzNi44My03MS42OSA1LjgzLTc4LS4zNi0uNjQtLjYyLTEuNjItLjc1LTIuMzktLjMxTC45OSAyOC42N2MtLjYyLjM2LS45OSAxLjAxLS45OSAxLjcydjU3LjJjMCAuNzEuMzggMS4zNi45OSAxLjcybDQ5LjM2IDI4LjIzYy45Mi41MyAyLjA5LjI0IDIuNjctLjY0Qzg2Ljg2IDY2LjA4IDEzMS45OCA5MSAxMzEuOTggOTFjLTM5LjAyLTM1Ljc1LTcyLjY2LTcuMDctNzguOTQtMS4wNS0uNjQuNjEtMS42MS43NC0yLjM4LjI5WiIgZmlsbD0iI0ZGRiIgb3BhY2l0eT0iMC44Ii8+PHBhdGggZD0iTTEzMC45NiAxLjA4Qzk3LjEyIDUxLjkgNTIgMjYuOTggNTIgMjYuOThjMzkuMDIgMzUuNzUgNzIuNjYgNy4wNyA3OC45NCAxLjA1LjY0LS42MSAxLjYxLS43NCAyLjM4LS4yOWwyNS42NSAxNC42NmMuNjIuMzYuOTkgMS4wMS45OSAxLjcydjI5LjczYzAgLjcxLS4zOCAxLjM2LS45OSAxLjcybC0yNi45OCAxNS40NHMtNDUtMjcuMDEtNzguOTYgMjUuOWMzOC43My0zNi44MyA3MS41OS01LjY5IDc3LjkxLjUxLjY0LjYyIDEuNjIuNzUgMi4zOS4zMWw0OS42NS0yOC40Yy42Mi0uMzYuOTktMS4wMS45OS0xLjcyVjMwLjM4YzAtLjcxLS4zOC0xLjM2LS45OS0xLjcyTDEzMy42My40M2MtLjkyLS41My0yLjA5LS4yNC0yLjY3LjY0WiIgZmlsbD0iI0ZGRiIgLz48L3N2Zz4K'
		);

		$submenu_pages_params = array(
			array(
				self::TOP_MENU_SLUG,
				__( 'All Connections', 'air-wp-sync-pro-plus' ),
				__( 'All Connections', 'air-wp-sync-pro-plus' ),
				apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
				'edit.php?post_type=airwpsync-connection',
			),
			array(
				self::TOP_MENU_SLUG,
				__( 'Add New', 'air-wp-sync-pro-plus' ),
				__( 'Add New', 'air-wp-sync-pro-plus' ),
				apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
				'post-new.php?post_type=airwpsync-connection',
			),
		);

		if ( $this->plugin_strings->has_string( 'admin.menu.documentation_link' ) ) {
			$submenu_pages_params[] = array(
				self::TOP_MENU_SLUG,
				__( 'Documentation', 'air-wp-sync-pro-plus' ),
				__( 'Documentation', 'air-wp-sync-pro-plus' ),
				apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ),
				$this->plugin_strings->get_string( 'admin.menu.documentation_link' ),
			);
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '"admin.menu.documentation_link" is missing from the plugin’s strings.' );
		}

		$submenu_pages_params = apply_filters( 'airwpsync/admin_submenu_pages_params', $submenu_pages_params );
		foreach ( $submenu_pages_params as $submenu_page_params ) {
			call_user_func_array( 'add_submenu_page', $submenu_page_params );
		}

		global $submenu;
		// Remove useless top menu entry.
		if ( 'air-wp-sync-pro-plus' === $submenu['air-wp-sync-pro-plus'][0][2] ) {
			array_shift( $submenu['air-wp-sync-pro-plus'] );
		}
		if ( __( 'Connections', 'air-wp-sync-pro-plus' ) === $submenu['air-wp-sync-pro-plus'][0][0] ) {
			array_shift( $submenu['air-wp-sync-pro-plus'] );
		}
	}

	/**
	 * Redirect top menu to sub menu page (default to connection list).
	 *
	 * @return void
	 */
	public function redirect_top_menu() {
		wp_safe_redirect( apply_filters( 'airwpsync/admin/top_menu_redirection_url', admin_url( 'edit.php?post_type=airwpsync-connection' ) ) );
		exit;
	}

	/**
	 * Display plugin header
	 */
	public function in_admin_header() {
		if ( apply_filters( 'airwpsync/should_display_admin_header', false ) ) {
			$view = include __DIR__ . '/views/header.php';
			$view( $this->plugin_context );
		}
	}

	/**
	 * Display admin header if we are on "airwpsync-connection" post type screen.
	 *
	 * @param bool $display_admin_header Display admin header.
	 *
	 * @return mixed|true
	 */
	public function should_display_admin_header( $display_admin_header ) {
		$screen = get_current_screen();
		if ( 'airwpsync-connection' === $screen->post_type ) {
			$display_admin_header = true;
		}
		return $display_admin_header;
	}

	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		wp_enqueue_style( 'air-wp-sync-pro-plus-admin', plugins_url( 'assets/css/admin-page.css', $this->plugin_context->get_plugin_file() ), false, $this->plugin_context->get_plugin_version() );
		wp_enqueue_script( 'air-wp-sync-pro-plus-admin', plugins_url( 'vendor_prefixed/wpconnect/x-wp-sync-core/src/Admin/assets/js/admin.js', $this->plugin_context->get_plugin_file() ), array( 'jquery' ), $this->plugin_context->get_plugin_version(), true );
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param string[] $actions An array of plugin action links. By default this can include
	 *                              'activate', 'deactivate', and 'delete'. With Multisite active
	 *                              this can also include 'network_active' and 'network_only' items.
	 */
	public function plugin_action_links( $actions ) {
		return array_merge(
			$actions,
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=air-wp-sync-pro-plus-settings' ) . '">' . esc_html__( 'Settings', 'air-wp-sync-pro-plus' ) . '</a>',
			)
		);
	}

	/**
	 * Add admin notices
	 */
	public function add_notices() {
		// Display site health alert if required.
		$this->site_heath_notice();
	}

	/**
	 * Display site health notice if required.
	 *
	 * @return void
	 */
	protected function site_heath_notice() {
		$current_screen = get_current_screen();
		if ( 'airwpsync-connection' === $current_screen->post_type || strpos( $current_screen->base, 'air-wp-sync-pro-plus' ) !== false ) {
			$site_health_check = Site_Health::get_instance()->check();
			if ( ! $site_health_check['all_good'] ) {
				$messages  = array();
				$notice_id = array( 'site_health' );
				foreach ( $site_health_check as $check_key => $check_result ) {
					if ( 'all_good' === $check_key ) {
						continue;
					}
					if ( true !== $check_result ) {
						$messages [] = '<p>' . $check_result . '</p>';
						$notice_id[] = $check_key;
					}
				}
				$notice_id  = implode( '_', $notice_id );
				$notice_id .= '_' . wp_date( 'Y-m' );

				$dismissed_notices = $this->options->get( 'dismissed_notices' );
				if ( ! $dismissed_notices ) {
					$dismissed_notices = array();
				}
				if ( isset( $dismissed_notices[ $notice_id ] ) ) {
					return;
				}

				if ( true !== $site_health_check['cron'] || true !== $site_health_check['can_perform_loopback'] ) {
					/* Translators: %s Site health page URL */
					$messages [] = '<p>' . sprintf( __( 'WP Cron does not sound to work correctly and it‘s required by our plugin. Please check <a href="%s">Site Health page</a> and <a href="https://wpconnect.co/blog/setup-cron-wordpress/" target="_blank">how to set up WP Cron correctly</a>.', 'air-wp-sync-pro-plus' ), admin_url( 'site-health.php' ) ) . '</p>';
				}

				$message            = implode( '', $messages );
				$dismiss_action_url = add_query_arg(
					array(
						'action'    => 'air_wp_sync_dismiss_notice',
						'notice_id' => $notice_id,
					),
					wp_nonce_url( admin_url( 'admin-ajax.php' ), 'air_wp_sync_dismiss_notice' )
				);
				echo wp_kses(
					sprintf(
						"<div class='notice notice-warning is-dismissible js-airwpsync-dismissible-notice' data-airwpsync-dismissible-action='%s'>{$message}</div>",
						esc_attr( $dismiss_action_url )
					),
					array(
						'div' => array(
							'class' => array(),
							'data-airwpsync-dismissible-action' => array(),
						),
						'p'   => array(),
						'br'  => array(),
						'a'   => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);

				add_action( 'admin_footer', array( $this, 'dismissible_notice_script' ) );
			}
		}
	}

	/**
	 * Output dismiss notice script.
	 *
	 * @return void
	 */
	public function dismissible_notice_script() {
		?><script>
			(function () {
				const dismissibleNotices = document.querySelectorAll('.js-airwpsync-dismissible-notice');
				dismissibleNotices.forEach(function (dismissibleNotice) {
					dismissibleNotice.addEventListener('click', function (e) {
						if (e.target && e.target.classList.contains('notice-dismiss')) {
							fetch(dismissibleNotice.getAttribute('data-airwpsync-dismissible-action'));
						}
					})
				});
			})();
		</script>
		<?php
	}

	/**
	 * Handle Ajax request to dismiss a notice.
	 *
	 * @return void
	 */
	public function ajax_dismiss_notice() {
		check_ajax_referer( 'air_wp_sync_dismiss_notice' );
		Helper::check_ajax_admin_user_access();

		$notice_id = isset( $_GET['notice_id'] ) ? sanitize_key( $_GET['notice_id'] ) : false;
		if ( ! $notice_id ) {
			wp_send_json_error();
		}

		$dismissed_notices = $this->options->get( 'dismissed_notices' );
		if ( ! $dismissed_notices ) {
			$dismissed_notices = array();
		}
		$dismissed_notices[ $notice_id ] = true;
		$this->options->set( 'dismissed_notices', $dismissed_notices );
		$this->options->save();
		wp_send_json_success();
	}
}
