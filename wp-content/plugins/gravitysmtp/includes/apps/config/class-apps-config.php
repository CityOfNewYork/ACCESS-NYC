<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Config;

use Gravity_Forms\Gravity_SMTP\Alerts\Endpoints\Save_Alerts_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Experimental_Features\Experiment_Features_Handler;
use Gravity_Forms\Gravity_SMTP\Tracking\Tracking_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Config;

class Apps_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';

	public function data() {
		$container         = Gravity_SMTP::container();
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$debug_log_enabled   = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_DEBUG_LOG_ENABLED, 'false' );
		$debug_log_enabled   = ! empty( $debug_log_enabled ) ? $debug_log_enabled !== 'false' : false;

		$test_mode = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE );
		$test_mode = ! empty( $test_mode ) ? $test_mode !== 'false' : false;

		$usage_analytics_enabled = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_USAGE_ANALYTICS, 'true' );
		$usage_analytics_enabled = ! empty( $usage_analytics_enabled ) ? $usage_analytics_enabled !== 'false' : false;

		// todo: @aaron from here to line 42, please refactor as you see fit. We need deep defaults maybe, or a pattern, and i need deep Booliesh hahaha unless what i did there is cool
		$experimental_features = $plugin_data_store->get_plugin_setting( Experiment_Features_Handler::ENABLED_EXPERIMENTS_PARAM, array() );

		if ( ! isset( $experimental_features[ 'alerts_management' ] ) ) {
			$experimental_features[ 'alerts_management' ] = false;
		}

		foreach( $experimental_features as $feature => $enabled ) {
			$experimental_features[ $feature ] = Booliesh::get( $enabled );
		}

		return array(
			'common' => array(
				'i18n' => array(
					'aria_label_collapsed_metabox'               => esc_html__( 'Expand', 'gravitysmtp' ),
					'aria_label_expanded_metabox'                => esc_html__( 'Collapse', 'gravitysmtp' ),
					'confirm_change_cancel'                      => esc_html__( 'Cancel', 'gravitysmtp' ),
					'confirm_change_confirm'                     => esc_html__( 'Confirm', 'gravitysmtp' ),
					'debug_log_enabled'                          => esc_html__( 'Debug Log Enabled', 'gravitysmtp' ),
					'test_mode_enabled'                          => esc_html__( 'Test Mode Enabled', 'gravitysmtp' ),
					'setting_locked'                             => esc_html__( 'This setting is locked due to a defined constant and cannot be modified.', 'gravitysmtp' ),
					'debug_messages'                             => array(
						/* translators: %1$s is the body of the ajax request. */
						'deleting_activity_log_rows'         => esc_html__( 'Deleting activity log rows: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the error. */
						'deleting_activity_log_rows_error'   => esc_html__( 'Error deleting activity log rows: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the body of the ajax request. */
						'deleting_all_debug_logs'           => esc_html__( 'Deleting all debug logs: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the error. */
						'deleting_all_debug_logs_error'     => esc_html__( 'Error deleting all debug logs: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the active connector they are saving settings for, %2$s is the body of the ajax request. */
						'saving_integration_settings'       => esc_html__( 'Saving integration settings for the %1$s connector: %2$s', 'gravitysmtp' ),
						/* translators: %1$s is the active connector they are saving settings for, %2$s is the error. */
						'saving_integration_settings_error' => esc_html__( 'Error saving integration settings for the %1$s connector: %2$s', 'gravitysmtp' ),
						/* translators: %1$s is the body of the ajax request. */
						'saving_plugin_settings'            => esc_html__( 'Saving plugin settings: %1$s', 'gravitysmtp' ),
						/* translators: %1$s is the error. */
						'saving_plugin_settings_error'      => esc_html__( 'Error saving plugin settings: %1$s', 'gravitysmtp' ),
					),
					'general_settings_usage_analytics_label'     => esc_html__( 'Share Gravity SMTP Analytics', 'gravitysmtp' ),
					/* translators: {{learn_link}} tags are replaced by opening and closing tags for a link to our learn more page for usage */
					'general_settings_usage_analytics_help_text' => esc_html__( 'We love improving the email sending experience for everyone in our community. By enabling analytics you can help us learn more about how our customers use Gravity SMTP. {{learn_link}}Learn more{{learn_link}}', 'gravitysmtp' ),
					'snackbar_api_save_success'                  => esc_html__( 'API settings saved', 'gravitysmtp' ),
					'snackbar_generic_update_error'              => esc_html__( 'Error saving setting', 'gravitysmtp' ),
					'snackbar_generic_update_success'            => esc_html__( 'Setting successfully updated', 'gravitysmtp' ),
					'snackbar_send_test_mail_error'              => esc_html__( 'Could not send test email; please check your logs', 'gravitysmtp' ),
					'snackbar_send_test_mail_success'            => esc_html__( 'Email successfully sent', 'gravitysmtp' ),
					'snackbar_activity_log_delete_error'         => esc_html__( 'Error deleting log entries', 'gravitysmtp' ),
					'snackbar_email_log_error'                   => esc_html__( 'Error getting email log for requested page', 'gravitysmtp' ),
					'snackbar_email_log_detail_generic_error'    => esc_html__( 'Error getting email log details', 'gravitysmtp' ),
					'snackbar_email_log_detail_empty_error'      => esc_html__( 'Error getting email log details, the log data was empty', 'gravitysmtp' ),
					'snackbar_email_log_delete_error'            => esc_html__( 'Error deleting email log', 'gravitysmtp' ),
					'snackbar_activity_log_delete_success'       => esc_html__( 'Email log successfully deleted', 'gravitysmtp' ),
					'snackbar_debug_log_delete_error'            => esc_html__( 'Error deleting debug log', 'gravitysmtp' ),
					'snackbar_debug_log_delete_success'          => esc_html__( 'Debug log successfully deleted', 'gravitysmtp' ),
					'snackbar_url_copied'                        => esc_html__( 'URL copied to clipboard', 'gravitysmtp' ),
					'test_mode_warning_notice'                   => esc_html__( 'Test mode is enabled, emails will not be sent.', 'gravitysmtp' ),
				),
				'data' => array(
					'constants' => array(
						'CAPS_DELETE_DEBUG_LOG'               => Roles::DELETE_DEBUG_LOG,
						'CAPS_DELETE_EMAIL_LOG'               => Roles::DELETE_EMAIL_LOG,
						'CAPS_DELETE_EMAIL_LOG_DETAILS'       => Roles::DELETE_EMAIL_LOG_DETAILS,
						'CAPS_EDIT_ALERTS'                    => Roles::EDIT_ALERTS,
						'CAPS_EDIT_ALERTS_SLACK_SETTINGS'     => Roles::EDIT_ALERTS_SLACK_SETTINGS,
						'CAPS_EDIT_ALERTS_TWILIO_SETTINGS'    => Roles::EDIT_ALERTS_TWILIO_SETTINGS,
						'CAPS_EDIT_DEBUG_LOG'                 => Roles::EDIT_DEBUG_LOG,
						'CAPS_EDIT_EMAIL_LOG'                 => Roles::EDIT_EMAIL_LOG,
						'CAPS_EDIT_EMAIL_LOG_DETAILS'         => Roles::EDIT_EMAIL_LOG_DETAILS,
						'CAPS_EDIT_EMAIL_LOG_SETTINGS'        => Roles::EDIT_EMAIL_LOG_SETTINGS,
						'CAPS_EDIT_DEBUG_LOG_SETTINGS'        => Roles::EDIT_DEBUG_LOG_SETTINGS,
						'CAPS_EDIT_EMAIL_MANAGEMENT_SETTINGS' => Roles::EDIT_EMAIL_MANAGEMENT_SETTINGS,
						'CAPS_EDIT_GENERAL_SETTINGS'          => Roles::EDIT_GENERAL_SETTINGS,
						'CAPS_EDIT_INTEGRATIONS'              => Roles::EDIT_INTEGRATIONS,
						'CAPS_EDIT_LICENSE_KEY'               => Roles::EDIT_LICENSE_KEY,
						'CAPS_EDIT_EXPERIMENTAL_FEATURES'     => Roles::EDIT_EXPERIMENTAL_FEATURES,
						'CAPS_EDIT_TEST_MODE'                 => Roles::EDIT_TEST_MODE,
						'CAPS_EDIT_UNINSTALL'                 => Roles::EDIT_UNINSTALL,
						'CAPS_EDIT_USAGE_ANALYTICS'           => Roles::EDIT_USAGE_ANALYTICS,
						'CAPS_VIEW_ALERTS'                    => Roles::VIEW_ALERTS,
						'CAPS_VIEW_ALERTS_SLACK_SETTINGS'     => Roles::VIEW_ALERTS_SLACK_SETTINGS,
						'CAPS_VIEW_ALERTS_TWILIO_SETTINGS'    => Roles::VIEW_ALERTS_TWILIO_SETTINGS,
						'CAPS_VIEW_DEBUG_LOG'                 => Roles::VIEW_DEBUG_LOG,
						'CAPS_VIEW_EMAIL_LOG'                 => Roles::VIEW_EMAIL_LOG,
						'CAPS_VIEW_EMAIL_LOG_DETAILS'         => Roles::VIEW_EMAIL_LOG_DETAILS,
						'CAPS_VIEW_EMAIL_LOG_PREVIEW'         => Roles::VIEW_EMAIL_LOG_PREVIEW,
						'CAPS_VIEW_EMAIL_LOG_SETTINGS'        => Roles::VIEW_EMAIL_LOG_SETTINGS,
						'CAPS_VIEW_DEBUG_LOG_SETTINGS'        => Roles::VIEW_DEBUG_LOG_SETTINGS,
						'CAPS_VIEW_EMAIL_MANAGEMENT_SETTINGS' => Roles::VIEW_EMAIL_MANAGEMENT_SETTINGS,
						'CAPS_VIEW_GENERAL_SETTINGS'          => Roles::VIEW_GENERAL_SETTINGS,
						'CAPS_VIEW_INTEGRATIONS'              => Roles::VIEW_INTEGRATIONS,
						'CAPS_VIEW_LICENSE_KEY'               => Roles::VIEW_LICENSE_KEY,
						'CAPS_VIEW_EXPERIMENTAL_FEATURES'     => Roles::VIEW_EXPERIMENTAL_FEATURES,
						'CAPS_VIEW_TEST_MODE'                 => Roles::VIEW_TEST_MODE,
						'CAPS_VIEW_TOOLS'                     => Roles::VIEW_TOOLS,
						'CAPS_VIEW_TOOLS_SENDATEST'           => Roles::VIEW_TOOLS_SENDATEST,
						'CAPS_VIEW_TOOLS_SYSTEMREPORT'        => Roles::VIEW_TOOLS_SYSTEMREPORT,
						'CAPS_VIEW_UNINSTALL'                 => Roles::VIEW_UNINSTALL,
						'CAPS_VIEW_USAGE_ANALYTICS'           => Roles::VIEW_USAGE_ANALYTICS,
						'CAPS_VIEW_DASHBOARD'                 => Roles::VIEW_DASHBOARD,
					),
					'debug_log_enabled'       => $debug_log_enabled,
					'experimental_features'   => $experimental_features,
					'param_keys'              => array(
						'alert_threshold_count'                   => Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_COUNT,
						'alert_threshold_interval'                => Save_Alerts_Settings_Endpoint::PARAM_ALERT_THRESHOLD_INTERVAL,
						'debug_log_enabled'                       => Save_Plugin_Settings_Endpoint::PARAM_DEBUG_LOG_ENABLED,
						'debug_log_retention'                     => Save_Plugin_Settings_Endpoint::PARAM_DEBUG_LOG_RETENTION,
						'enabled_experimental_features'           => Experiment_Features_Handler::ENABLED_EXPERIMENTS_PARAM,
						'event_log_enabled'                       => Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED,
						'event_log_retention'                     => Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION,
						'license_key'                             => Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY,
						'notify_when_email_sending_fails_enabled' => Save_Plugin_Settings_Endpoint::PARAM_NOTIFY_WHEN_EMAIL_SENDING_FAILS_ENABLED,
						'open_tracking'                           => Tracking_Service_Provider::SETTING_OPEN_TRACKING,
						'save_attachments_enabled'                => Save_Plugin_Settings_Endpoint::PARAM_SAVE_ATTACHMENTS_ENABLED,
						'save_email_body_enabled'                 => Save_Plugin_Settings_Endpoint::PARAM_SAVE_EMAIL_BODY_ENABLED,
						'slack_alerts'                            => Save_Alerts_Settings_Endpoint::PARAM_SLACK_ALERTS,
						'slack_alerts_enabled'                    => Save_Plugin_Settings_Endpoint::PARAM_SLACK_ALERTS_ENABLED,
						'test_mode'                               => Save_Plugin_Settings_Endpoint::PARAM_TEST_MODE,
						'twilio_alerts'                           => Save_Alerts_Settings_Endpoint::PARAM_TWILIO_ALERTS,
						'twilio_alerts_enabled'                   => Save_Plugin_Settings_Endpoint::PARAM_TWILIO_ALERTS_ENABLED,
						'usage_analytics'                         => Save_Plugin_Settings_Endpoint::PARAM_USAGE_ANALYTICS,
					),
					'locked_settings'         => $this->get_locked_settings(),
					'test_mode_enabled'       => $test_mode,
					'usage_analytics_enabled' => $usage_analytics_enabled,
					'usage_analytics_link'    => 'https://docs.gravitysmtp.com/about-additional-data-collection/',
				),
			),
			'hmr_dev'     => defined( 'GRAVITYSMTP_ENABLE_HMR' ) && GRAVITYSMTP_ENABLE_HMR,
			'public_path' => trailingslashit( Gravity_SMTP::get_base_url() ) . 'assets/js/dist/',
		);
	}

	private function get_locked_settings() {
		$return = array();

		$defined_constants = array_filter( get_defined_constants(), function( $constant ) {
			return strpos( $constant, 'GRAVITYSMTP_' ) !== false;
		}, ARRAY_FILTER_USE_KEY );

		foreach( $defined_constants as $constant => $constant_value ) {
			$setting_name = strtolower( str_replace( 'GRAVITYSMTP_', '', $constant ) );
			$return[] = $setting_name;
		}

		return $return;
	}

}
