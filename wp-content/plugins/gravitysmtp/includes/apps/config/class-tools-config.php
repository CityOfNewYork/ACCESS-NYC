<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Config;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Check_Background_Tasks_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Enums\Connector_Status_Enum;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\View_Log_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_Tools\Config;
use Gravity_Forms\Gravity_Tools\Logging\Log_Line;
use Gravity_Forms\Gravity_Tools\Utils\Common;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Tools_Config extends Config {

	const SETTING_DEBUG_LOG_ENABLED   = 'debug_log_enabled';
	const SETTING_DEBUG_LOG_RETENTION = 'debug_log_retention';
	const SETTING_DEBUG_LOG_LEVEL     = 'debug_log_level';

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	protected $sensitive_keys = [
		'license_key',
	];

	public function should_enqueue() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		if ( $page !== 'gravitysmtp-tools' ) {
			return false;
		}

		return true;
	}

	public function data() {
		$container = Gravity_SMTP::container();
		/**
		 * @var Debug_Log_Model $debug_model
		 */
		$debug_model = $container->get( Logging_Service_Provider::DEBUG_LOG_MODEL );
		$search_term = filter_input( INPUT_GET, 'search_term' );
		$search_type = filter_input( INPUT_GET, 'search_type' );

		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		if ( ! empty( $search_type ) ) {
			$search_type = htmlspecialchars( $search_type );
		}

		$count = $debug_model->count( $search_term, $search_type );

		$opts     = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$per_page = $opts->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_PER_PAGE, 20 );

		return array(
			'components' => array(
				'tools' => array(
					'data'      => array(
						'version'                 => GF_GRAVITY_SMTP_VERSION,
						'route_path'              => admin_url( 'admin.php' ),
						'nav_item_param_key'      => 'tab',
						'nav_items'               => array(
							array(
								'param' => 'send-a-test',
								'label' => esc_html__( 'Send a Test', 'gravitysmtp' ),
								'icon'  => 'beaker',
							),
							array(
								'param' => 'scheduled-events',
								'label' => esc_html__( 'Scheduled Events', 'gravitysmtp' ),
								'icon'  => 'clock',
							),
							array(
								'param' => 'import-export',
								'label' => esc_html__( 'Import/Export', 'gravitysmtp' ),
								'icon'  => 'switch-horizontal',
							),
							array(
								'param' => 'debug-log',
								'label' => esc_html__( 'Debug Log', 'gravitysmtp' ),
								'icon'  => 'debug',
							),
							array(
								'param' => 'system-report',
								'label' => esc_html__( 'System Report', 'gravitysmtp' ),
								'icon'  => 'clipboard-check',
							),
							array(
								'param' => 'permissions',
								'label' => esc_html__( 'Permissions', 'gravitysmtp' ),
								'icon'  => 'closed-lock',
							),
						),
						'send_a_test'             => array(
							'send_with_options' => array(),
						),
						'system_report'           => $this->get_system_report(),
						'system_report_clipboard' => $this->get_clipboard_string(),
						'debug_log_settings'      => $this->get_debug_log_settings(),
						'debug_log'               => array(
							'ajax_grid_pagination_url' => trailingslashit( GF_GRAVITY_SMTP_PLUGIN_URL ) . 'includes/logging/endpoints/get-paginated-debug-log-items.php',
							'data_grid'                => array(
								'columns'            => $this->get_debug_log_columns(),
								'column_style_props' => $this->get_debug_log_column_style_props(),
								'data'               => array(
									'value'   => $this->get_debug_log_data_rows(),
									'default' => $this->get_debug_log_demo_data_rows(),
								),
							),
							'initial_row_count'        => isset( $count ) ? intval( $count ) : 0,
							'initial_load_timestamp'   => current_time( 'mysql', true ),
							'rows_per_page'            => $per_page,
						),
						'caps' => array(
							Roles::VIEW_TOOLS              => current_user_can( Roles::VIEW_TOOLS ),
							Roles::VIEW_TOOLS_SENDATEST    => current_user_can( Roles::VIEW_TOOLS_SENDATEST ),
							Roles::VIEW_TOOLS_SYSTEMREPORT => current_user_can( Roles::VIEW_TOOLS_SYSTEMREPORT ),
							Roles::VIEW_DEBUG_LOG          => current_user_can( Roles::VIEW_DEBUG_LOG ),
						),
					),
					'i18n' => array(
						'error_alert_title'           => esc_html__( 'Error Saving', 'gravitysmtp' ),
						'error_alert_generic_message' => esc_html__( 'Could not save, please check your logs.', 'gravitysmtp' ),
						'error_alert_close_text'      => esc_html__( 'Close', 'gravitysmtp' ),
						'debug_log'                   => array(
							'top_heading'              => esc_html__( 'Debug Log', 'gravitysmtp' ),
							'data_grid'                => array(
								'top_heading'                       => esc_html__( 'Debug Results', 'gravitysmtp' ),
								'clear_search_aria_label'           => esc_html__( 'Clear search', 'gravitysmtp' ),
								'empty_title'                       => esc_html__( 'No debug logs', 'gravitysmtp' ),
								'empty_message'                     => esc_html__( 'You will see debug log details when they occur on your site.', 'gravitysmtp' ),
								'grid_controls_search_button_label' => esc_html__( 'Search', 'gravitysmtp' ),
								'grid_controls_search_placeholder'  => esc_html__( 'Search', 'gravitysmtp' ),
								'pagination_next'                   => esc_html__( 'Next', 'gravitysmtp' ),
								'pagination_prev'                   => esc_html__( 'Previous', 'gravitysmtp' ),
								'pagination_next_aria_label'        => esc_html__( 'Next Page', 'gravitysmtp' ),
								'pagination_prev_aria_label'        => esc_html__( 'Previous Page', 'gravitysmtp' ),
								'search_no_results_title'           => esc_html__( 'No results found', 'gravitysmtp' ),
								'search_no_results_message'         => esc_html__( 'No results found for your search', 'gravitysmtp' ),
							),
							'details_dialog_heading'   => esc_html__( 'Debug Log Details', 'gravitysmtp' ),
							'details_dialog_event'     => esc_html__( 'Event', 'gravitysmtp' ),
							'details_dialog_date'      => esc_html__( 'Date', 'gravitysmtp' ),
							'details_dialog_log'       => esc_html__( 'Log', 'gravitysmtp' ),
							'snackbar_debug_log_error' => esc_html__( 'Error getting debug log for requested page', 'gravitysmtp' ),
						),
						'debug_messages'              => array(
							/* translators: %1$s is the body of the ajax request. */
							'fetching_debug_log_page'       => esc_html__( 'Fetching debug log page: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error message. */
							'fetching_debug_log_page_error' => esc_html__( 'Error fetching debug log page: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the body of the ajax request. */
							'sending_test_email'            => esc_html__( 'Sending test email: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error message. */
							'sending_test_email_error'      => esc_html__( 'Error sending test email: %1$s', 'gravitysmtp' ),
						),
						'send_a_test'                 => array(
							'top_heading'                                   => esc_html__( 'Send a Test', 'gravitysmtp' ),
							'top_content'                                   => esc_html__( 'Verify your SMTP setup to ensure reliable email delivery.', 'gravitysmtp' ),
							'send_test_box_heading'                         => esc_html__( 'Send a Test Email', 'gravitysmtp' ),
							'send_test_box_input_label'                     => esc_html__( 'Send To', 'gravitysmtp' ),
							'send_test_box_input_placeholder'               => esc_html__( 'Enter an email address', 'gravitysmtp' ),
							'send_test_box_input_description'               => esc_html__( 'Enter the email address you want to send the test email to.', 'gravitysmtp' ),
							'send_test_box_send_with_label'                 => esc_html__( 'Send With', 'gravitysmtp' ),
							'send_test_box_send_with_description'           => esc_html__( 'Choose which integration you would like to send a test with.', 'gravitysmtp' ),
							'send_test_box_send_as_html_label'              => esc_html__( 'HTML', 'gravitysmtp' ),
							'send_test_box_send_as_html_description'        => esc_html__( 'Send test email as HTML.', 'gravitysmtp' ),
							'send_test_box_alerts_button_label'             => esc_html__( 'Send Test', 'gravitysmtp' ),
							'send_test_box_try_again_button_label'          => esc_html__( 'Try Again', 'gravitysmtp' ),
							'send_test_box_error_issue_heading'             => esc_html__( 'Issue Detected:', 'gravitysmtp' ),
							'send_test_box_error_possible_reasons_heading'  => esc_html__( 'Possible Reasons for Error:', 'gravitysmtp' ),
							'send_test_box_error_recommended_steps_heading' => esc_html__( 'Recommended Steps:', 'gravitysmtp' ),
							'send_test_box_view_log_button_label'           => esc_html__( 'View Full Error Log', 'gravitysmtp' ),
							'send_test_box_copy_log_button_label'           => esc_html__( 'Copy Error Log', 'gravitysmtp' ),
							'send_test_box_copy_log_success_message'        => esc_html__( 'Error log copied', 'gravitysmtp' ),
						),
						'system_report'               => array(
							'top_heading'          => esc_html__( 'System Report', 'gravitysmtp' ),
							'top_content'          => esc_html__( 'The system report contains useful technical information to help troubleshooting issues.', 'gravitysmtp' ),
							'copy_system_report'   => esc_html__( 'Copy System Report', 'gravitysmtp' ),
							'system_report_copied' => esc_html__( 'System report copied to clipboard', 'gravitysmtp' ),
						),
					),
					'endpoints' => array(),
				),
			)
		);
	}

	protected function get_debug_log_settings() {
		$container = Gravity_SMTP::container();
		/**
		 * @var Data_Store_Router $plugin_data_store
		 */
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		return array(
			self::SETTING_DEBUG_LOG_ENABLED   => $plugin_data_store->get_plugin_setting( self::SETTING_DEBUG_LOG_ENABLED, false ),
			self::SETTING_DEBUG_LOG_RETENTION => $plugin_data_store->get_plugin_setting( self::SETTING_DEBUG_LOG_RETENTION, '7' ),
		);
	}

	protected function get_system_report() {
		$gravity_smtp_data          = $this->get_gravity_smtp_data();
		$wordpress_environment_data = $this->get_wordpress_environment_data();
		$active_plugins_data        = $this->get_active_plugins_data();
		$web_server_data            = $this->get_web_server_data();
		$php_data                   = $this->get_php_data();
		$database_server_data       = $this->get_database_server_data();
		$database_table_data        = $this->get_database_table_data();
		$date_and_time_data         = $this->get_date_and_time_data();
		$debug_data                 = $this->get_debug_data();
		$constants_data             = $this->get_defined_constants();
		$integrations_data          = $this->get_integrations_data();
		$translations               = $this->get_translations_data();

		$system_report = array(
			array(
				'title'  => esc_html__( 'Gravity SMTP', 'gravitysmtp' ),
				'groups' => $this->get_groups( $gravity_smtp_data ),
				'key'    => 'gravitysmtp',
			),
			array(
				'title'  => esc_html__( 'WordPress Environment', 'gravitysmtp' ),
				'groups' => $this->get_groups( $wordpress_environment_data ),
				'key'    => 'wordpress-environment',
			),
		);

		if ( ! empty( $active_plugins_data ) ) {
			$system_report[] = array(
				'title'  => esc_html__( 'Active Plugins', 'gravitysmtp' ),
				'groups' => $this->get_groups( $active_plugins_data, true ),
				'key'    => 'active-plugins',
			);
		}

		$system_report = array_merge(
			$system_report,
			array(
				array(
					'title'  => esc_html__( 'Web Server', 'gravitysmtp' ),
					'groups' => $this->get_groups( $web_server_data ),
					'key'    => 'web-server',
				),
				array(
					'title'  => esc_html__( 'PHP', 'gravitysmtp' ),
					'groups' => $this->get_groups( $php_data ),
					'key'    => 'php',
				),
				array(
					'title'  => esc_html__( 'Database Server', 'gravitysmtp' ),
					'groups' => $this->get_groups( $database_server_data ),
					'key'    => 'database-server',
				),
				array(
					'title'  => esc_html__( 'Database Tables', 'gravitysmtp' ),
					'groups' => $this->get_groups( $database_table_data ),
					'key'    => 'database-tables',
				),
				array(
					'title'  => esc_html__( 'Date and Time', 'gravitysmtp' ),
					'groups' => $this->get_groups( $date_and_time_data ),
					'key'    => 'date-and-time',
				),
				array(
					'title' => esc_html__( 'Debug Log', 'gravitysmtp' ),
					'groups' => $this->get_groups( $debug_data ),
					'key' => 'debug-log',
				),
				array(
					'title' => esc_html__( 'Integration Settings', 'gravitysmtp' ),
					'groups' => $this->get_groups( $integrations_data ),
					'key' => 'integrations-settings',
				),
				array(
					'title' => esc_html__( 'Translations', 'gravitysmtp' ),
					'groups' => $this->get_groups( $translations ),
					'key' => 'translations',
				),
			)
		);

		if ( ! empty( $constants_data ) ) {
			$system_report[] = array(
				'title' => esc_html__( 'Defined Constants', 'gravitysmtp' ),
				'groups' => $this->get_groups( $constants_data ),
				'key' => 'defined-constants',
			);
		}

		return $system_report;
	}

	protected function get_groups( $data, $as_html = false ) {
		return array_map( function( $data_item ) {
			return array(
				'terms'        => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $data_item['label'],
						'size'    => 'text-sm',
						'weight'  => 'semibold',
						'tagName' => 'span',
						'asHtml'  => isset( $data_item['label_as_html'] ) ? $data_item['label_as_html'] : false,
					),
				),
				'descriptions' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => $data_item['value'],
						'size'    => 'text-sm',
						'tagName' => 'span',
						'asHtml'  => isset( $data_item['value_as_html'] ) ? $data_item['value_as_html'] : false,
					),
				),
			);
		}, $data );
	}

	protected function get_translations_data() {
		$items = array(
			array(
				'label'        => esc_html__( 'Site Locale', 'gravitysmtp' ),
				'label_export' => 'Site Locale',
				'value'        => get_locale(),
			),
		);

		if ( function_exists( 'get_user_locale' ) ) {
			$items[] = array(
				// translators: %d: The ID of the currently logged in user.
				'label'        => sprintf( esc_html__( 'User (ID: %d) Locale', 'gravitysmtp' ), get_current_user_id() ),
				'label_export' => sprintf( 'User (ID: %d) Locale', get_current_user_id() ),
				'value'        => get_user_locale(),
			);
		}

		$translations = $this->get_installed_translations();


		$items[] = array(
			'label'        => esc_html__( 'Installed Translations', 'gravitysmtp' ),
			'label_export' => 'Installed Translations',
			'value'        => empty( $translations ) ? esc_html__( 'No translations installed', 'gravitysmtp' ) : implode( ', ', $translations ),
		);

		return $items;
	}

	protected function get_installed_translations() {
		$domain = 'gravitysmtp';
		$files = glob( WP_LANG_DIR . '/plugins/' . 'gravitysmtp-*.mo' );

		if ( ! is_array( $files ) ) {
			return array();
		}

		$translations = array();

		foreach ( $files as $file ) {
			$translations[ str_replace( $domain . '-', '', basename( $file, '.mo' ) ) ] = $file;
		}

		return array_keys( $translations );
	}

	protected function get_clipboard_string() {
		$data = array(
			array(
				'title' => 'Gravity SMTP',
				'data'  => $this->get_gravity_smtp_data(),
			),
			array(
				'title' => 'WordPress Environment',
				'data'  => $this->get_wordpress_environment_data(),
			),
		);

		$active_plugins_data = $this->get_active_plugins_data();
		if ( ! empty( $active_plugins_data ) ) {
			$data[] = array(
				'title' => 'Active Plugins',
				'data'  => $active_plugins_data,
			);
		}

		$data = array_merge(
			$data,
			array(
				array(
					'title' => 'Web Server',
					'data'  => $this->get_web_server_data(),
				),
				array(
					'title' => 'PHP',
					'data'  => $this->get_php_data(),
				),
				array(
					'title' => 'Database Server',
					'data'  => $this->get_database_server_data(),
				),
				array(
					'title' => 'Database Tables',
					'data'  => $this->get_database_table_data(),
				),
				array(
					'title' => 'Date and Time',
					'data'  => $this->get_date_and_time_data(),
				),
				array(
					'title' => 'Debug Log',
					'data' => $this->get_debug_data( false ),
				),
				array(
					'title' => 'Integration Settings',
					'data' => $this->get_integrations_data(),
				),
				array(
					'title' => 'Translations',
					'data' => $this->get_translations_data(),
				),
				array(
					'title' => 'Defined Constants',
					'data' => $this->get_defined_constants(),
				),
			)
		);

		return array_reduce( $data, function( $carry, $item ) {
			$carry .= $item['title'] . "\n";

			if ( ! empty( $item['data'] ) ) {
				foreach ( $item['data'] as $data_item ) {
					if ( isset( $data_item['value_export'] ) ) {
						$carry .= $data_item['label_export'] . ': ' . $data_item['value_export'] . "\n";
						continue;
					}

					$carry .= $data_item['label_export'] . ': ' . $data_item['value'] . "\n";
				}
			}

			$carry .= "\n";

			return $carry;
		}, '' );
	}

	protected function get_gravity_smtp_data() {
		return array(
			array(
				'label'        => esc_html__( 'Version', 'gravitysmtp' ),
				'label_export' => 'Version',
				'value'        => GF_GRAVITY_SMTP_VERSION,
			),
		);
	}

	protected function get_wordpress_environment_data() {
		$wp_cron_disabled  = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		$alternate_wp_cron = defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON;

		$args = array(
			'timeout'   => 2,
			'body'      => 'test',
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$query_args = array(
			'action'   => Check_Background_Tasks_Endpoint::ACTION_NAME,
			'security' => wp_create_nonce( Check_Background_Tasks_Endpoint::ACTION_NAME ),
		);

		$url = add_query_arg( $query_args, admin_url( 'admin-ajax.php' ) );

		$response = wp_remote_post( $url, $args );

		// Trims the background tasks response to prevent extraneous characters causing unexpected content in the response.
		$background_tasks = trim( wp_remote_retrieve_body( $response ) ) == 'ok';

		$filters_to_check = array(
			'pre_wp_mail' => has_filter( 'pre_wp_mail' ),
		);

		$registered_filters = array_keys( array_filter( $filters_to_check ) );

		return array(
			array(
				'label'        => esc_html__( 'Home URL', 'gravitysmtp' ),
				'label_export' => 'Home URL',
				'value'        => get_home_url(),
			),
			array(
				'label'        => esc_html__( 'Site URL', 'gravitysmtp' ),
				'label_export' => 'Site URL',
				'value'        => get_site_url(),
			),
			array(
				'label'        => esc_html__( 'REST API Base URL', 'gravitysmtp' ),
				'label_export' => 'REST API Base URL',
				'value'        => rest_url(),
			),
			array(
				'label'        => esc_html__( 'WordPress Version', 'gravitysmtp' ),
				'label_export' => 'WordPress Version',
				'value'        => get_bloginfo( 'version' ),
			),
			array(
				'label'        => esc_html__( 'WordPress Multisite', 'gravitysmtp' ),
				'label_export' => 'WordPress Multisite',
				'value'        => is_multisite() ? esc_html__( 'Yes', 'gravitysmtp' ) : esc_html__( 'No', 'gravitysmtp' ),
				'value_export' => is_multisite() ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'WordPress Memory Limit', 'gravitysmtp' ),
				'label_export' => 'WordPress Memory Limit',
				'value'        => WP_MEMORY_LIMIT,
			),
			array(
				'label'        => esc_html__( 'WordPress Debug Mode', 'gravitysmtp' ),
				'label_export' => 'WordPress Debug Mode',
				'value'        => WP_DEBUG ? esc_html__( 'Yes', 'gravitysmtp' ) : esc_html__( 'No', 'gravitysmtp' ),
				'value_export' => WP_DEBUG ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'WordPress Debug Log', 'gravitysmtp' ),
				'label_export' => 'WordPress Debug Log',
				'value'        => WP_DEBUG_LOG ? esc_html__( 'Yes', 'gravitysmtp' ) : esc_html__( 'No', 'gravitysmtp' ),
				'value_export' => WP_DEBUG_LOG ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'WordPress Script Debug Mode', 'gravitysmtp' ),
				'label_export' => 'WordPress Script Debug Mode',
				'value'        => SCRIPT_DEBUG ? __( 'Yes', 'gravitysmtp' ) : __( 'No', 'gravitysmtp' ),
				'value_export' => SCRIPT_DEBUG ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'WordPress Cron', 'gravitysmtp' ),
				'label_export' => 'WordPress Cron',
				'value'        => ! $wp_cron_disabled ? __( 'Yes', 'gravitysmtp' ) : __( 'No', 'gravitysmtp' ),
				'value_export' => ! $wp_cron_disabled ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'WordPress Alternate Cron', 'gravitysmtp' ),
				'label_export' => 'WordPress Alternate Cron',
				'value'        => $alternate_wp_cron ? __( 'Yes', 'gravitysmtp' ) : __( 'No', 'gravitysmtp' ),
				'value_export' => $alternate_wp_cron ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Background Tasks', 'gravitysmtp' ),
				'label_export' => 'Background tasks',
				'value'        => $background_tasks ? __( 'Yes', 'gravitysmtp' ) : __( 'No', 'gravitysmtp' ),
				'value_export' => $background_tasks ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Registered Filters', 'gravitysmtp' ),
				'label_export' => 'Registered filters',
				'value'        => empty( $registered_filters ) ? esc_html__( 'N/A', 'gravitysmtp' ) : join( ', ', $registered_filters ),
				'value_export' => empty( $registered_filters ) ? 'N/A' : join( ', ', $registered_filters ),
			),
		);
	}

	protected function get_active_plugins_data() {
		$plugins = array();

		foreach ( get_plugins() as $plugin_path => $plugin ) {
			// If plugin is not active, skip it.
			if ( ! is_plugin_active( $plugin_path ) ) {
				continue;
			}

			// If this plugin is Gravity SMTP, skip it.
			if ( 'gravitysmtp/gravitysmtp.php' === $plugin_path ) {
				continue;
			}

			$label  = isset( $plugin['PluginURI'] ) && ! empty( $plugin['PluginURI'] )
				? '<a class="gform-link" href="' . esc_url( $plugin['PluginURI'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $plugin['Name'] ) . '</a>'
				: esc_html( $plugin['Name'] );

			$author = $plugin['Author'];
			if ( isset( $plugin['AuthorURI'] ) && ! empty( $plugin['AuthorURI'] ) ) {
				$author = '<a class="gform-link" href="' . esc_url( $plugin['AuthorURI'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $plugin['Author'] ) . '</a>';
			} else {
				$author = preg_replace_callback( '/(<a[^>]*>)/', function( $matches ) {
					preg_match( '/class="[^"]*"/', $matches[1], $class_matches );
					if ( empty( $class_matches ) ) {
						return str_replace( '<a', '<a class="gform-link"', $matches[1] );
					}

					return preg_replace( '/class="([^"]*)"/', 'class="$1 gform-link"', $matches[1] );
				}, $plugin['Author'] );
			}

			$value = wp_kses_post(
				sprintf(
				/* translators: 1: Plugin author and URL. 2: Plugin version. */
					__( 'by %1$s - %2$s', 'gravitysmtp' ),
					$author,
					$plugin['Version']
				)
			);

			$value_export = esc_html(
				sprintf(
				/* translators: 1: Plugin author and URL. 2: Plugin version. */
					__( 'by %1$s - %2$s', 'gravitysmtp' ),
					strip_tags( $plugin['Author'] ),
					$plugin['Version']
				)
			);

			$plugins[] = array(
				'label'         => $label,
				'label_export'  => strip_tags( $plugin['Name'] ),
				'label_as_html' => true,
				'value'         => $value,
				'value_export'  => $value_export,
				'value_as_html' => true,
			);
		}

		return $plugins;
	}

	protected function get_web_server_data() {
		return array(
			array(
				'label'        => esc_html__( 'Software', 'gravitysmtp' ),
				'label_export' => 'Software',
				'value'        => esc_html( $_SERVER['SERVER_SOFTWARE'] ),
			),
			array(
				'label'        => esc_html__( 'Port', 'gravitysmtp' ),
				'label_export' => 'Port',
				'value'        => esc_html( $_SERVER['SERVER_PORT'] ),
			),
			array(
				'label'        => esc_html__( 'Document Root', 'gravitysmtp' ),
				'label_export' => 'Document Root',
				'value'        => esc_html( $_SERVER['DOCUMENT_ROOT'] ),
			),
		);
	}

	protected function get_php_data() {
		$curl_version = null;
		if ( function_exists('curl_version') ) {
			$curl_version_info = curl_version();
			if ( is_array( $curl_version_info ) && isset( $curl_version_info['version'] ) ) {
				$curl_version = $curl_version_info['version'];
			}
		}

		return array(
			array(
				'label'        => esc_html__( 'Version', 'gravitysmtp' ),
				'label_export' => 'Version',
				'value'        => esc_html( phpversion() ),
			),
			array(
				'label'        => esc_html__( 'Memory Limit', 'gravitysmtp' ) . ' (memory_limit)',
				'label_export' => 'Memory Limit',
				'value'        => esc_html( ini_get( 'memory_limit' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum Execution Time', 'gravitysmtp' ) . ' (max_execution_time)',
				'label_export' => 'Maximum Execution Time',
				'value'        => esc_html( ini_get( 'max_execution_time' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum File Upload Size', 'gravitysmtp' ) . ' (upload_max_filesize)',
				'label_export' => 'Maximum File Upload Size',
				'value'        => esc_html( ini_get( 'upload_max_filesize' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum File Uploads', 'gravitysmtp' ) . ' (max_file_uploads)',
				'label_export' => 'Maximum File Uploads',
				'value'        => esc_html( ini_get( 'max_file_uploads' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum Post Size', 'gravitysmtp' ) . ' (post_max_size)',
				'label_export' => 'Maximum Post Size',
				'value'        => esc_html( ini_get( 'post_max_size' ) ),
			),
			array(
				'label'        => esc_html__( 'Maximum Input Variables', 'gravitysmtp' ) . ' (max_input_vars)',
				'label_export' => 'Maximum Input Variables',
				'value'        => esc_html( ini_get( 'max_input_vars' ) ),
			),
			array(
				'label'        => esc_html__( 'cURL Enabled', 'gravitysmtp' ),
				'label_export' => 'cURL Enabled',
				'value'        => function_exists( 'curl_init' )
					? esc_html(
						sprintf(
						/* translators: %s: cURL version. */
							__( 'Yes (version %s)', 'gravitysmtp' ),
							$curl_version
						)
					)
					: esc_html__( 'No', 'gravitysmtp' ),
				'value_export' => function_exists( 'curl_init' ) ? sprintf( 'Yes (version %s)', $curl_version ) : 'No',
			),
			array(
				'label'        => esc_html__( 'OpenSSL', 'gravitysmtp' ),
				'label_export' => 'OpenSSL',
				'value'        => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT . ' (' . OPENSSL_VERSION_NUMBER . ')' : __( 'No', 'gravitysmtp' ),
				'value_export' => defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT . ' (' . OPENSSL_VERSION_NUMBER . ')' : 'No',
			),
			array(
				'label'        => esc_html__( 'Mcrypt Enabled', 'gravitysmtp' ),
				'label_export' => 'Mcrypt Enabled',
				'value'        => function_exists( 'mcrypt_encrypt' ) ? esc_html__( 'Yes', 'gravitysmtp' ) : esc_html__( 'No', 'gravitysmtp' ),
				'value_export' => function_exists( 'mcrypt_encrypt' ) ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Mbstring Enabled', 'gravitysmtp' ),
				'label_export' => 'Mbstring Enabled',
				'value'        => function_exists( 'mb_strlen' ) ? esc_html__( 'Yes', 'gravitysmtp' ) : esc_html__( 'No', 'gravitysmtp' ),
				'value_export' => function_exists( 'mb_strlen' ) ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Loaded Extensions', 'gravitysmtp' ),
				'label_export' => 'Loaded Extensions',
				'value'        => join( ', ', get_loaded_extensions() ),
			),
		);
	}

	protected function get_debug_data( $as_html = true ) {
		$container = Gravity_SMTP::container();
		/**
		 * @var Data_Store_Router $plugin_data_store
		 */
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$enabled           = $plugin_data_store->get_plugin_setting( Tools_Config::SETTING_DEBUG_LOG_ENABLED, false );
		$retention         = $plugin_data_store->get_plugin_setting( Tools_Config::SETTING_DEBUG_LOG_RETENTION, '7' );
		$link              = 'N/A';
		$enabled           = ! ( $enabled === false || $enabled === 0 || $enabled === '0' || $enabled === 'false' );

		if ( $enabled ) {
			$key = get_option( View_Log_Endpoint::OPTION_VERIFICATION_KEY );
			$path = sprintf( 'admin-ajax.php?action=%s&key=%s', View_Log_Endpoint::ACTION_NAME, $key );
			if ( $as_html ) {
				$link = '<a target="_blank" rel="noopener noreferrer" href="' . admin_url( $path ) . '">' . admin_url( $path ) . '</a>';
			} else {
				$link = admin_url( $path );
			}
		}

		return array(
			array(
				'label'        => esc_html__( 'Debug Log Enabled', 'gravitysmtp' ),
				'label_export' => 'Debug Log Enabled',
				'value'        => $enabled ? 'Yes' : 'No',
			),
			array(
				'label'        => esc_html__( 'Debug Retention Period', 'gravitysmtp' ),
				'label_export' => 'Debug Retention Period',
				'value'        => $retention,
			),
			array(
				'label'        => esc_html__( 'Debug Log Link', 'gravitysmtp' ),
				'label_as_html' => false,
				'value_as_html' => true,
				'label_export' => 'Debug Log Link',
				'value'        => $link,
			),
		);
	}

	protected function get_defined_constants() {
		$defined_constants = array_filter( get_defined_constants(), function ( $constant ) {
			return strpos( $constant, 'GRAVITYSMTP_' ) !== false;
		}, ARRAY_FILTER_USE_KEY );

		$connector_factory = Gravity_SMTP::$container->get( Connector_Service_Provider::CONNECTOR_FACTORY );
		$connector_types   = Gravity_SMTP::$container->get( Connector_Service_Provider::REGISTERED_CONNECTORS );

		$sensitive_fields_by_connector = [];
		foreach ( $connector_types as $name => $class ) {
			$sensitive_fields_by_connector[ $name ] = array_fill_keys( $connector_factory->create( $name )->get_sensitive_fields(), true );
		}

		$sensitive_lookup = array_fill_keys( $this->sensitive_keys, true );

		return array_map( function ( $key ) use ( $defined_constants, $sensitive_fields_by_connector, $sensitive_lookup ) {
			$value        = $defined_constants[ $key ];
			$value_string = is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value;
			$setting_key  = strtolower( str_replace( 'GRAVITYSMTP_', '', $key ) );

			$should_obfuscate = isset( $sensitive_lookup[ $setting_key ] ) || $this->is_sensitive_connector_key( $setting_key, $sensitive_fields_by_connector );

			return [
				'label'        => $key,
				'label_export' => $key,
				'value'        => $should_obfuscate && ! empty( $value_string ) ? str_repeat( '*', strlen( $value_string ) ) : $value_string,
			];
		}, array_keys( $defined_constants ) );
	}

	protected function is_sensitive_connector_key( $setting_key, $sensitive_fields_by_connector ) {
		foreach ( $sensitive_fields_by_connector as $connector_name => $sensitive_fields ) {
			$connector_prefix_length = strlen( $connector_name ) + 1;

			if ( strncmp( $setting_key, strtolower( $connector_name ) . '_', $connector_prefix_length ) === 0 ) {
				return isset( $sensitive_fields[ substr( $setting_key, $connector_prefix_length ) ] );
			}

			if ( isset( $sensitive_fields[ $setting_key ] ) ) {
				return true;
			}
		}

		return false;
	}

	protected function get_integrations_data() {
		$container = Gravity_SMTP::container();
		$values    = array();

		/**
		 * @var Data_Store_Router $data
		 */
		$data               = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
		$connector_statuses = $data->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, array() );
		$connector_names    = $container->get( Connector_Service_Provider::NAME_MAP );
		$enabled_connectors = array();

		foreach ( $connector_names as $slug => $label ) {
			if ( array_key_exists( $slug, $connector_statuses ) && $connector_statuses[ $slug ] === 'true' ) {
				$enabled_connectors[] = $label;
			}
		}

		if ( empty( $enabled_connectors ) ) {
			$enabled_connectors[] = esc_html__( 'No Integrations Enabled', 'gravitysmtp' );
		}

		$values[] = array(
			'label'        => esc_html__( 'Enabled Integrations', 'gravitysmtp' ),
			'label_export' => 'Enabled Integrations',
			'value'        => implode( ', ', $enabled_connectors ),
		);

		$primary_connector = $data->get_connector_status_of_type( Connector_Status_Enum::PRIMARY, __( 'No Primary Integration Enabled', 'gravitysmtp' ) );
		$backup_connector  = $data->get_connector_status_of_type( Connector_Status_Enum::BACKUP, __( 'No Backup Integration Enabled', 'gravitysmtp' ) );

		$values[] = array(
			'label'        => esc_html__( 'Primary Integration', 'gravitysmtp' ),
			'label_export' => 'Primary Integration',
			'value'        => isset( $connector_names[ $primary_connector ] ) ? $connector_names[ $primary_connector ] : $primary_connector,
		);

		$values[] = array(
			'label'        => esc_html__( 'Backup Integration', 'gravitysmtp' ),
			'label_export' => 'Backup Integration',
			'value'        => isset( $connector_names[ $backup_connector ] ) ? $connector_names[ $backup_connector ] : $backup_connector,
		);

		return $values;
	}

	protected function get_database_server_data() {
		global $wpdb;

		$db_version = Common::get_db_version();
		$db_type = Common::get_dbms_type();

		return array(
			array(
				'label'        => esc_html__( 'Database Management System', 'gravitysmtp' ),
				'label_export' => 'Database Management System',
				'value'        => esc_html( $db_type ),
			),
			array(
				'label'        => esc_html__( 'Version', 'gravitysmtp' ),
				'label_export' => 'Version',
				'value'        => esc_html( $db_version ),
			),
			array(
				'label'        => esc_html__( 'Database Character Set', 'gravitysmtp' ),
				'label_export' => 'Database Character Set',
				'value'        => esc_html( ( Common::get_dbms_type() === 'SQLite' ) ? $wpdb->charset : $wpdb->get_var( 'SELECT @@character_set_database' ) ),
			),
			array(
				'label'        => esc_html__( 'Database Collation', 'gravitysmtp' ),
				'label_export' => 'Database Collation',
				'value'        => esc_html( ( Common::get_dbms_type() === 'SQLite' ) ? ( empty( $wpdb->collate ) ? 'N/A' : $wpdb->collate ) : $wpdb->get_var( 'SELECT @@collation_database' ) ),
			),
		);
	}

	protected function get_database_table_data() {
		global $wpdb;

		$tables = Gravity_SMTP::get_table_names();
		$data   = array();

		foreach ( $tables as $table ) {
			$full_table_name = $wpdb->prefix . $table;
			$table_exists    = $wpdb->get_var( "SHOW TABLES LIKE '{$full_table_name}'" ) === $full_table_name;

			$data[] = array(
				'label'        => $table,
				'label_export' => $table,
				'value'        => $table_exists ? esc_html__( 'Exists', 'gravitysmtp' ) : esc_html__( 'Missing', 'gravitysmtp' ),
				'value_export' => $table_exists ? 'Exists' : 'Missing',
			);
		}

		return $data;
	}

	protected function get_date_and_time_data() {
		global $wpdb;

		$db_date  = $wpdb->get_var( 'SELECT utc_timestamp()' );
		$php_date = date( 'Y-m-d H:i:s' );

		$date_option = trim( get_option( 'date_format' ) );
		$time_option = trim( get_option( 'time_format' ) );
		$date_format = $date_option ? $date_option : 'Y-m-d';
		$time_format = $time_option ? $time_option : 'H:i';

		$gmt_db_date             = mysql2date( 'G', $db_date );
		$local_db_date           = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $gmt_db_date ) ) );
		$formatted_local_db_date = sprintf(
		/* translators: 1: date, 2: time */
			__( '%1$s at %2$s', 'gravitysmtp' ),
			date_i18n( $date_format, $local_db_date, true ),
			date_i18n( $time_format, $local_db_date, true )
		);

		$gmt_php_date             = mysql2date( 'G', $php_date );
		$local_php_date           = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $gmt_php_date ) ) );
		$formatted_local_php_date = sprintf(
		/* translators: 1: date, 2: time */
			__( '%1$s at %2$s', 'gravitysmtp' ),
			date_i18n( $date_format, $local_php_date, true ),
			date_i18n( $time_format, $local_php_date, true )
		);

		return array(
			array(
				'label'        => esc_html__( 'WordPress (Local) Timezone', 'gravitysmtp' ),
				'label_export' => 'WordPress (Local) Timezone',
				'value'        => $this->get_timezone(),
			),
			array(
				'label'        => esc_html__( 'MySQL - Universal time (UTC)', 'gravitysmtp' ),
				'label_export' => 'MySQL (UTC)',
				'value'        => esc_html( $db_date ),
			),
			array(
				'label'        => esc_html__( 'MySQL - Local time', 'gravitysmtp' ),
				'label_export' => 'MySQL (Local)',
				'value'        => esc_html( $formatted_local_db_date ),
			),
			array(
				'label'        => esc_html__( 'PHP - Universal time (UTC)', 'gravitysmtp' ),
				'label_export' => 'PHP (UTC)',
				'value'        => esc_html( $php_date ),
			),
			array(
				'label'        => esc_html__( 'PHP - Local time', 'gravitysmtp' ),
				'label_export' => 'PHP (Local)',
				'value'        => esc_html( $formatted_local_php_date ),
			),
		);
	}

	protected function get_timezone() {
		$tzstring = get_option( 'timezone_string' );

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
			$tzstring = '';
		}

		if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists
			$current_offset = get_option( 'gmt_offset' );
			if ( 0 == $current_offset ) {
				$tzstring = 'UTC+0';
			} elseif ( $current_offset < 0 ) {
				$tzstring = 'UTC' . $current_offset;
			} else {
				$tzstring = 'UTC+' . $current_offset;
			}
		}

		return $tzstring;
	}

	protected function get_debug_log_columns() {
		$columns = array(
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'id',
				'props'           => array(
					'content' => esc_html__( 'ID', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
				'variableLoader'  => true,
			),
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'status',
				'props'           => array(
					'content' => esc_html__( 'Status', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
				'variableLoader'  => true,
			),
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'date',
				'props'           => array(
					'content' => __( 'Date & Time', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
				'variableLoader'  => true,
			),
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'log',
				'props'           => array(
					'content' => esc_html__( 'Log', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
				'variableLoader'  => true,
			),
			array(
				'component'       => 'Text',
				'hideWhenLoading' => true,
				'key'             => 'actions',
				'props'           => array(
					'content' => esc_html__( 'Actions', 'gravitysmtp' ),
					'size'    => 'text-sm',
					'weight'  => 'medium',
				),
				'sortable'        => true,
				'variableLoader'  => true,
			),
		);

		return apply_filters( 'gravitysmtp_debug_log_columns', $columns );
	}

	protected function get_debug_log_column_style_props() {
		$props = array(
			'id'      => array( 'flex' => '0 0 60px' ),
			'status'  => array( 'flex' => '0 0 92px' ),
			'date'    => array( 'flex' => '0 0 188px' ),
			'log'     => array( 'flexBasis' => 'auto' ),
			'actions' => array( 'flex' => '0 0 84px' ),
		);

		return apply_filters( 'gravitysmtp_debug_log_column_style_props', $props );
	}

	protected function get_debug_log_data_rows() {
		$container = Gravity_SMTP::container();
		/**
		 * @var Debug_Log_Model $debug_model
		 */
		$debug_model = $container->get( Logging_Service_Provider::DEBUG_LOG_MODEL );
		$opts        = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$current_page = filter_input( INPUT_GET, 'log_page', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $current_page ) ) {
			$current_page = 1;
		}

		$search_term = filter_input( INPUT_GET, 'search_term' );
		$search_type = filter_input( INPUT_GET, 'search_type' );
		if ( ! empty( $search_term ) ) {
			$search_term = htmlspecialchars( $search_term );
		}

		if ( ! empty( $search_type ) ) {
			$search_type = htmlspecialchars( $search_type );
		}

		$per_page = $opts->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_PER_PAGE, 20 );
		$lines    = $debug_model->paginate( $current_page, $per_page, false, $search_term, $search_type );

		return $debug_model->lines_as_data_grid( $lines );
	}

	protected function get_debug_log_demo_data_rows() {
		return array(
			array(
				'id'    => array(
					'component' => 'Text',
					'props' => array(
						'content' => '1',
						'size'    => 'text-xs',
						'weight'  => 'medium',
					)
				),
				'status' => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'hasDot' => false,
						'label'  => 'Debug',
						'status' => 'active',
					),
				),
				'date' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => '2021-07-01 at 12:00 am',
						'size'    => 'text-sm',
					),
				),
				'log' => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-subject' ),
						'data'          => array( 'id' => 1 ),
						'disabled'      => false,
						'label'         => 'Gravity\\GravitySMTP\\Internal\\DataStores\\System\\DataSynchronizer::get_current_orders()',
						'type'          => 'unstyled',
					),
				),
				'actions' => array(
					'component'  => 'Box',
					'components' => array(
						array(
							'component' => 'Button',
							'props' => array(
								'action'        => 'view',
								'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-action' ),
								'data'          => array( 'id' => 1 ),
								'disabled'      => false,
								'icon'          => 'eye',
								'iconPrefix'    => 'gravitysmtp-admin-icon',
								'label'         => 'View',
								'size'          => 'size-height-s',
								'type'          => 'icon-white',
							),
						),
					),
				),
			),
			array(
				'id'    => array(
					'component' => 'Text',
					'props' => array(
						'content' => '2',
						'size'    => 'text-xs',
						'weight'  => 'medium',
					)
				),
				'status' => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'hasDot' => false,
						'label'  => 'Fatal',
						'status' => 'error',
					),
				),
				'date' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => '2022-09-25 at 7:33 am',
						'size'    => 'text-sm',
					),
				),
				'log' => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-subject' ),
						'data'          => array( 'id' => 2 ),
						'disabled'      => false,
						'label'         => 'Gravity\\GravitySMTP\\Internal\\DataStores\\System\\DataSynchronizer::get_current_orders()',
						'type'          => 'unstyled',
					),
				),
				'actions' => array(
					'component'  => 'Box',
					'components' => array(
						array(
							'component' => 'Button',
							'props' => array(
								'action'        => 'view',
								'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-action' ),
								'data'          => array( 'id' => 2 ),
								'disabled'      => false,
								'icon'          => 'eye',
								'iconPrefix'    => 'gravitysmtp-admin-icon',
								'label'         => 'View',
								'size'          => 'size-height-s',
								'type'          => 'icon-white',
							),
						),
					),
				),
			),
			array(
				'id'    => array(
					'component' => 'Text',
					'props' => array(
						'content' => '3',
						'size'    => 'text-xs',
						'weight'  => 'medium',
					)
				),
				'status' => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'hasDot' => false,
						'label'  => 'Error',
						'status' => 'error',
					),
				),
				'date' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => '2022-11-04 at 4:56 pm',
						'size'    => 'text-sm',
					),
				),
				'log' => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-subject' ),
						'data'          => array( 'id' => 3 ),
						'disabled'      => false,
						'label'         => 'Gravity\\GravitySMTP\\Internal\\DataStores\\System\\DataSynchronizer::get_current_orders()',
						'type'          => 'unstyled',
					),
				),
				'actions' => array(
					'component'  => 'Box',
					'components' => array(
						array(
							'component' => 'Button',
							'props' => array(
								'action'        => 'view',
								'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-action' ),
								'data'          => array( 'id' => 3 ),
								'disabled'      => false,
								'icon'          => 'eye',
								'iconPrefix'    => 'gravitysmtp-admin-icon',
								'label'         => 'View',
								'size'          => 'size-height-s',
								'type'          => 'icon-white',
							),
						),
					),
				),
			),
			array(
				'id'    => array(
					'component' => 'Text',
					'props' => array(
						'content' => '4',
						'size'    => 'text-xs',
						'weight'  => 'medium',
					)
				),
				'status' => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'hasDot' => false,
						'label'  => 'Info',
						'status' => 'gray',
					),
				),
				'date' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => '2023-01-03 at 1:12 pm',
						'size'    => 'text-sm',
					),
				),
				'log' => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-subject' ),
						'data'          => array( 'id' => 4 ),
						'disabled'      => false,
						'label'         => 'Gravity\\GravitySMTP\\Internal\\DataStores\\System\\DataSynchronizer::get_current_orders()',
						'type'          => 'unstyled',
					),
				),
				'actions' => array(
					'component'  => 'Box',
					'components' => array(
						array(
							'component' => 'Button',
							'props' => array(
								'action'        => 'view',
								'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-action' ),
								'data'          => array( 'id' => 4 ),
								'disabled'      => false,
								'icon'          => 'eye',
								'iconPrefix'    => 'gravitysmtp-admin-icon',
								'label'         => 'View',
								'size'          => 'size-height-s',
								'type'          => 'icon-white',
							),
						),
					),
				),
			),
			array(
				'id'    => array(
					'component' => 'Text',
					'props' => array(
						'content' => '5',
						'size'    => 'text-xs',
						'weight'  => 'medium',
					)
				),
				'status' => array(
					'component' => 'StatusIndicator',
					'props'     => array(
						'hasDot' => false,
						'label'  => 'Warning',
						'status' => 'warning',
					),
				),
				'date' => array(
					'component' => 'Text',
					'props'     => array(
						'content' => '2023-02-03 at 6:01 am',
						'size'    => 'text-sm',
					),
				),
				'log' => array(
					'component' => 'Button',
					'props'     => array(
						'action'        => 'view',
						'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-subject' ),
						'data'          => array( 'id' => 5 ),
						'disabled'      => false,
						'label'         => 'Gravity\\GravitySMTP\\Internal\\DataStores\\System\\DataSynchronizer::get_current_orders()',
						'type'          => 'unstyled',
					),
				),
				'actions' => array(
					'component'  => 'Box',
					'components' => array(
						array(
							'component' => 'Button',
							'props' => array(
								'action'        => 'view',
								'customClasses' => array( 'gravitysmtp-tools-app__debug-log-table-action' ),
								'data'          => array( 'id' => 5 ),
								'disabled'      => false,
								'icon'          => 'eye',
								'iconPrefix'    => 'gravitysmtp-admin-icon',
								'label'         => 'View',
								'size'          => 'size-height-s',
								'type'          => 'icon-white',
							),
						),
					),
				),
			),
		);
	}

}
