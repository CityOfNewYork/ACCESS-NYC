<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Config;

use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Tracking\Tracking_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Users\Roles;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Config;
use Gravity_Forms\Gravity_Tools\License\License_Statuses;
use Gravity_Forms\Gravity_SMTP\Experimental_Features\Experiment_Features_Handler;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;

class Settings_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	public function should_enqueue() {
		if ( ! is_admin() ) {
			return false;
		}

		$page = filter_input( INPUT_GET, 'page' );

		if ( ! is_string( $page ) ) {
			return false;
		}

		$page = htmlspecialchars( $page );

		if ( $page !== 'gravitysmtp-settings' ) {
			return false;
		}

		return true;
	}

	public function data() {
		$container         = Gravity_SMTP::container();
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$license_key  = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY, '' );
		$key_is_empty = empty( $license_key );
		$is_valid     = null;

		if ( ! $key_is_empty ) {
			$license_info = $container->get( Updates_Service_Provider::LICENSE_API_CONNECTOR )->check_license( $license_key );
			$is_valid     = License_Statuses::VALID_KEY === $license_info->get_status();
		}

		$email_log_enabled        = Booliesh::get( $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_ENABLED, 'true' ) );
		$save_email_body_enabled  = Booliesh::get( $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_SAVE_EMAIL_BODY_ENABLED, 'true' ) );
		$save_attachments_enabled = Booliesh::get( $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_SAVE_ATTACHMENTS_ENABLED, 'false' ) );
		$email_log_retention      = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_EVENT_LOG_RETENTION, 7 );
		$max_records_value        = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_MAX_EVENT_RECORDS, 0 );
		$open_tracking_enabled    = Booliesh::get( $plugin_data_store->get_plugin_setting( Tracking_Service_Provider::SETTING_OPEN_TRACKING, 'false' ) );

		$debug_log_enabled   = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_DEBUG_LOG_ENABLED, 'false' );
		$debug_log_enabled   = ! empty( $debug_log_enabled ) ? $debug_log_enabled !== 'false' : false;
		$debug_log_retention = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_DEBUG_LOG_RETENTION, 7 );

		$primary_locked = defined( 'GRAVITYSMTP_INTEGRATION_PRIMARY' );
		$backup_locked  = defined( 'GRAVITYSMTP_INTEGRATION_BACKUP' );

		// @translators: %d is an integer representing the maximum number of records to store in the log.
		$max_records_message = esc_html__( 'The Email Log is set to store a maximum of %d records. Any records over that limit will be deleted, starting with oldest records first.', 'gravitysmtp' );

		return array(
			'components' => array(
				'settings' => array(
					'i18n'      => array(
						'error_alert_title'           => esc_html__( 'Error Saving', 'gravitysmtp' ),
						'error_alert_generic_message' => esc_html__( 'Could not save, please check your logs.', 'gravitysmtp' ),
						'error_alert_close_text'      => esc_html__( 'Close', 'gravitysmtp' ),
						'debug_messages'              => array(
							/* translators: %1$s is the body of the ajax request. */
							'uninstalling_plugin'       => esc_html__( 'Uninstalling plugin: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error. */
							'uninstalling_plugin_error' => esc_html__( 'Error uninstalling plugin: %1$s', 'gravitysmtp' ),
						),
						'unsaved_changes_title'       => esc_html__( 'Unsaved Changes', 'gravitysmtp' ),
						'unsaved_changes_message'     => esc_html__( 'You have unsaved changes. Are you sure you want to leave this page?', 'gravitysmtp' ),
						'settings'                    =>
							array(
								'top_heading'                                                   => esc_html__( 'Settings', 'gravitysmtp' ),
								'top_content'                                                   => '',
								'license_box_heading'                                           => esc_html__( 'License', 'gravitysmtp' ),
								'license_box_content'                                           => esc_html__( 'A valid license key is required for access to automatic plugin upgrades and product support.', 'gravitysmtp' ),
								'license_box_input_label'                                       => esc_html__( 'License Key', 'gravitysmtp' ),
								'license_box_input_help_text'                                   => esc_html__( 'Enter your license key to gain access to plugin updates.', 'gravitysmtp' ),
								'license_box_input_link_text'                                   => esc_html__( 'Already purchased?', 'gravitysmtp' ),
								'license_box_button_text'                                       => esc_html__( 'Save License', 'gravitysmtp' ),
								'license_valid'                                                 => esc_html__( 'License key successfully validated!', 'gravitysmtp' ),
								'license_invalid'                                               => esc_html__( 'Invalid license key entered. Please check your license key and try again.', 'gravitysmtp' ),
								'experiments_box_heading'                                       => esc_html__( 'Experimental Features', 'gravitysmtp' ),
								'experiments_box_content'                                       => esc_html__( 'These features are works-in-progress, so you may find some bugs along the way.', 'gravitysmtp' ),
								'experiments_box_toggle_label'                                  => esc_html__( 'Alerts', 'gravitysmtp' ),
								'experiments_box_toggle_help_text'                              => esc_html__( 'Get notified via webhook or SMS (Twilio) when emails fail to send.', 'gravitysmtp' ),
								'experiments_box_toggle_label_opens'                            => esc_html__( 'Open Tracking', 'gravitysmtp' ),
								'experiments_box_toggle_help_text_opens'                        => esc_html__( 'Track when recipients open emails sent by the system.', 'gravitysmtp' ),
								'email_digest_box_heading'                                      => esc_html__( 'Email Digest Notification', 'gravitysmtp' ),
								'email_digest_box_content'                                      => esc_html__( 'Keep track of your email communication with ease using our Email Digest Notification feature. Receive regular email updates on your email activity and address potential issues promptly. Stay in control of your WordPress email communication and never miss an important message again.', 'gravitysmtp' ),
								'email_digest_box_summary_toggle_label'                         => esc_html__( 'Enable Digest Summary', 'gravitysmtp' ),
								'email_digest_box_notification_day_dropdown_label'              => esc_html__( 'Notification Day', 'gravitysmtp' ),
								'email_digest_box_notification_day_dropdown_help_text'          => esc_html__( 'Select when you want the email digest to be sent.', 'gravitysmtp' ),
								'email_digest_box_notification_email_addresses_input_label'     => esc_html__( 'Notification Email Addresses', 'gravitysmtp' ),
								'email_digest_box_notification_email_addresses_input_help_text' => esc_html__( 'This is a hint text to help users.', 'gravitysmtp' ),
								'email_digest_box_html_mode_toggle_label'                       => esc_html__( 'Enable HTML Mode', 'gravitysmtp' ),
								'email_digest_box_button_text'                                  => esc_html__( 'Save Settings', 'gravitysmtp' ),
								'test_mode_box_heading'                                         => esc_html__( 'Test Mode', 'gravitysmtp' ),
								/* translators: %s: opening and closing anchor tags */
								'test_mode_box_content_1'                                       => esc_html__( 'When test mode is on, your site will not send out any emails. If you turn on %semail logging%s, all emails will be stored in the Email Logs.', 'gravitysmtp' ),
								'test_mode_box_toggle_help_text'                                => esc_html__( 'Note: Some WordPress plugins use their own email delivery system instead of the standard wp_mail() function. Test mode might not block emails sent by these plugins. Consult their documentation to learn how to enable test emails.', 'gravitysmtp' ),
								'test_mode_box_toggle_label'                                    => esc_html__( 'Enable Test Mode', 'gravitysmtp' ),
								'general_settings_box_heading'                                  => esc_html__( 'General Settings', 'gravitysmtp' ),
								'uninstall_box_heading'                                         => esc_html__( 'Uninstall', 'gravitysmtp' ),
								'uninstall_box_content'                                         => esc_html__( 'This operation deletes ALL Gravity SMTP settings. If you continue, you will NOT be able to retrieve these settings.', 'gravitysmtp' ),
								'uninstall_box_button_label'                                    => esc_html__( 'Erase ALL Gravity SMTP Data', 'gravitysmtp' ),
								'uninstall_dialog_confirm_change_heading'                       => esc_html__( 'Confirm Delete', 'gravitysmtp' ),
								'uninstall_dialog_confirm_change_content'                       => esc_html__( 'This operation deletes ALL Gravity SMTP settings. If you continue, you will NOT be able to retrieve these settings.', 'gravitysmtp' ),
								'uninstall_dialog_confirm_change_confirm'                       => esc_html__( 'Delete', 'gravitysmtp' ),
								'error_uninstalling_message'                                    => esc_html__( 'There was an error uninstalling Gravity SMTP', 'gravitysmtp' ),
							),
						'integrations'                =>
							array(
								'top_heading'                 => esc_html__( 'Integrations', 'gravitysmtp' ),
								'top_content'                 => __( "Select and configure the integration you would like to use to send emails from this site. Don't see an integration you're looking for?", 'gravitysmtp' ),
								'suggestion_link_text'        => esc_html__( 'Suggest an integration.', 'gravitysmtp' ),
								'lower_heading'               => __( 'Alerts & Notifications', 'gravitysmtp' ),
								'lower_content'               => __( 'Don\'t miss important notifications, leads, or sales again. Get notified instantly through Slack, SMS, WhatsApp, or Telegram if there are any issues with your SMTP service.', 'gravitysmtp' ),
								'card_activate'               => esc_html__( 'Activate Integration', 'gravitysmtp' ),
								'card_toggle'                 => esc_html__( 'Currently Enabled', 'gravitysmtp' ),
								'card_learn_more'             => esc_html__( 'Learn More', 'gravitysmtp' ),
								'card_more_settings'          => esc_html__( 'More Settings', 'gravitysmtp' ),
								'card_settings'               => esc_html__( 'Settings', 'gravitysmtp' ),
								'card_connected'              => esc_html__( 'Connected', 'gravitysmtp' ),
								'card_configured'             => esc_html__( 'Configured', 'gravitysmtp' ),
								'card_primary'                => esc_html__( 'Primary', 'gravitysmtp' ),
								'card_backup'                 => esc_html__( 'Backup', 'gravitysmtp' ),
								'card_not_configured'         => esc_html__( 'Not Configured', 'gravitysmtp' ),
								'integration_settings_error'  => esc_html__( 'There was an error saving your settings', 'gravitysmtp' ),
								'integration_settings_cancel' => esc_html__( 'Cancel', 'gravitysmtp' ),
								'integration_settings_apply'  => esc_html__( 'Save Changes', 'gravitysmtp' ),
								'integration_settings_saved'  => esc_html__( 'Saved', 'gravitysmtp' ),
								'confirm_change_heading'      => esc_html__( 'Confirm Change', 'gravitysmtp' ),
								'confirm_change_content'      => __( 'Please confirm that you\'d like to switch the active email integration.', 'gravitysmtp' ),
								/* translators: %1$s is the integration name */
								'set_primary_integration'     => esc_html__( '%1$s set as primary integration', 'gravitysmtp' ),
								/* translators: %1$s is the integration name */
								'set_backup_integration'      => esc_html__( '%1$s set as backup integration', 'gravitysmtp' ),
								'primary_disabled_heading'    => esc_html__( 'Primary Integration Disabled', 'gravitysmtp' ),
								'primary_disabled_content'    => esc_html__( 'You have disabled your primary email integration. To continue sending emails via Gravity SMTP, please enable a backup integration or set and enable a new primary integration.', 'gravitysmtp' ),
							),
						'emails'                      =>
							array(
								'top_heading'                     => esc_html__( 'Email Management', 'gravitysmtp' ),
								'top_content'                     => __( "WordPress, by default, will send out emails for many events on your site. Using the toggles below, you can decide exactly which emails you'd like enabled.", 'gravitysmtp' ),
								'email_notifications_box_heading' => esc_html__( 'Manage Emails', 'gravitysmtp' ),
							),
						'logging'                     =>
							array(
								'top_heading'                                    => esc_html__( 'Email Logging', 'gravitysmtp' ),
								'top_content'                                    => esc_html__( 'Email logging keeps copies of all emails sent from your WordPress site, so you can review your sent emails and check their delivery status.', 'gravitysmtp' ),
								'logging_box_heading'                            => esc_html__( 'Email Logging', 'gravitysmtp' ),
								'enable_log_label'                               => esc_html__( 'Enable Log', 'gravitysmtp' ),
								'enable_log_helper_text'                         => esc_html__( 'Keep copies of all emails sent from your site.', 'gravitysmtp' ),
								'save_email_body_label'                          => esc_html__( 'Save Email Body', 'gravitysmtp' ),
								'save_email_body_helper_text'                    => esc_html__( 'Store the email body for all emails sent from your site.', 'gravitysmtp' ),
								'save_attachments_label'                         => esc_html__( 'Save Attachments', 'gravitysmtp' ),
								'save_attachments_helper_text'                   => esc_html__( 'Store attachments on the server in the uploads folder.', 'gravitysmtp' ),
								'track_open_label'                               => esc_html__( 'Open Email Tracking', 'gravitysmtp' ),
								'track_open_helper_text'                         => esc_html__( 'Track viewed emails by recipients.', 'gravitysmtp' ),
								'email_log_retention_label'                      => esc_html__( 'Log Retention Period', 'gravitysmtp' ),
								'email_log_retention_helper_text'                => esc_html__( 'Email logs older than the selected timeframe will be permanently deleted.', 'gravitysmtp' ),
								'email_log_max_records_helper_text'              => sprintf( $max_records_message, $max_records_value ),
								'error_saving_snackbar_message'                  => esc_html__( 'There was an error saving the settings', 'gravitysmtp' ),
								'debug_logging_box_heading'                      => esc_html__( 'Debug Logging', 'gravitysmtp' ),
								'enable_debug_log_label'                         => esc_html__( 'Enable Debug Log', 'gravitysmtp' ),
								'enable_debug_log_helper_text'                   => esc_html__( 'When enabled email sending errors debugging events will be logged, allowing you to detect email sending issues.', 'gravitysmtp' ),
								'debug_log_retention_label'                      => esc_html__( 'Debug Log Retention Period', 'gravitysmtp' ),
								'debug_log_retention_helper_text'                => esc_html__( 'Debug events older than the selected period will be permanently deleted from the database.', 'gravitysmtp' ),
								'view_activity_log_button_text'                  => esc_html__( 'View Email Log', 'gravitysmtp' ),
								'delete_activity_log_button_text'                => esc_html__( 'Delete Email Log', 'gravitysmtp' ),
								'view_debug_log_button_text'                     => esc_html__( 'View Debug Log', 'gravitysmtp' ),
								'copy_debug_log_button_text'                     => esc_html__( 'Copy Debug Log Link', 'gravitysmtp' ),
								'delete_debug_log_button_text'                   => esc_html__( 'Delete Debug Log', 'gravitysmtp' ),
								'delete_debug_log_dialog_confirm_change_heading' => esc_html__( 'Confirm Delete', 'gravitysmtp' ),
								'delete_email_log_dialog_confirm_change_content' => esc_html__( 'This operation deletes ALL email logs. If you continue, you will NOT be able to retrieve these logs.', 'gravitysmtp' ),
								'delete_debug_log_dialog_confirm_change_content' => esc_html__( 'This operation deletes ALL debug logs. If you continue, you will NOT be able to retrieve these logs.', 'gravitysmtp' ),
								'delete_debug_log_dialog_confirm_change_confirm' => esc_html__( 'Delete', 'gravitysmtp' ),
							),
					),
					'data'      => array(
						'license_key'                => $license_key,
						'license_key_is_valid'       => $is_valid,
						'version'                    => GF_GRAVITY_SMTP_VERSION,
						'email_log_settings'         => array(
							'email_log_enabled'            => $email_log_enabled,
							'email_log_retention'          => $email_log_retention,
							'email_log_url'                => admin_url( 'admin.php?page=gravitysmtp-activity-log' ),
							'log_retention_period_enabled' => ! Booliesh::get( $max_records_value ),
							'max_records'                  => $max_records_value,
							'max_records_set'              => Booliesh::get( $max_records_value ),
							'open_tracking_enabled'        => $open_tracking_enabled,
							'retention_options'            => $this->get_email_log_retention_options(),
							'save_attachments_enabled'     => $save_attachments_enabled,
							'save_email_body_enabled'      => $save_email_body_enabled,
						),
						'debug_log_settings'         => array(
							'debug_log_enabled'   => $debug_log_enabled,
							'debug_log_retention' => $debug_log_retention,
							'debug_log_url'       => admin_url( 'admin.php?page=gravitysmtp-tools&tab=debug-log' ),
							'retention_options'   => $this->get_debug_log_retention_options(),
						),
						'caps'                       => array(
							Roles::DELETE_DEBUG_LOG               => current_user_can( Roles::DELETE_DEBUG_LOG ),
							Roles::DELETE_EMAIL_LOG               => current_user_can( Roles::DELETE_EMAIL_LOG ),
							Roles::EDIT_ALERTS                    => current_user_can( Roles::EDIT_ALERTS ),
							Roles::EDIT_ALERTS_SLACK_SETTINGS     => current_user_can( Roles::EDIT_ALERTS_SLACK_SETTINGS ),
							Roles::EDIT_ALERTS_TWILIO_SETTINGS    => current_user_can( Roles::EDIT_ALERTS_TWILIO_SETTINGS ),
							Roles::EDIT_DEBUG_LOG_SETTINGS        => current_user_can( Roles::EDIT_DEBUG_LOG_SETTINGS ),
							Roles::EDIT_EMAIL_LOG_SETTINGS        => current_user_can( Roles::EDIT_EMAIL_LOG_SETTINGS ),
							Roles::EDIT_EMAIL_MANAGEMENT_SETTINGS => current_user_can( Roles::EDIT_EMAIL_MANAGEMENT_SETTINGS ),
							Roles::EDIT_INTEGRATIONS              => current_user_can( Roles::EDIT_INTEGRATIONS ),
							Roles::EDIT_LICENSE_KEY               => current_user_can( Roles::EDIT_LICENSE_KEY ),
							Roles::EDIT_EXPERIMENTAL_FEATURES     => current_user_can( Roles::EDIT_EXPERIMENTAL_FEATURES ),
							Roles::EDIT_TEST_MODE                 => current_user_can( Roles::EDIT_TEST_MODE ),
							Roles::EDIT_UNINSTALL                 => current_user_can( Roles::EDIT_UNINSTALL ),
							Roles::EDIT_USAGE_ANALYTICS           => current_user_can( Roles::EDIT_USAGE_ANALYTICS ),
							Roles::VIEW_ALERTS                    => current_user_can( Roles::VIEW_ALERTS ),
							Roles::VIEW_ALERTS_SLACK_SETTINGS     => current_user_can( Roles::VIEW_ALERTS_SLACK_SETTINGS ),
							Roles::VIEW_ALERTS_TWILIO_SETTINGS    => current_user_can( Roles::VIEW_ALERTS_TWILIO_SETTINGS ),
							Roles::VIEW_DEBUG_LOG                 => current_user_can( Roles::VIEW_DEBUG_LOG ),
							Roles::VIEW_DEBUG_LOG_SETTINGS        => current_user_can( Roles::VIEW_DEBUG_LOG_SETTINGS ),
							Roles::VIEW_EMAIL_LOG                 => current_user_can( Roles::VIEW_EMAIL_LOG ),
							Roles::VIEW_EMAIL_LOG_SETTINGS        => current_user_can( Roles::VIEW_EMAIL_LOG_SETTINGS ),
							Roles::VIEW_EMAIL_MANAGEMENT_SETTINGS => current_user_can( Roles::VIEW_EMAIL_MANAGEMENT_SETTINGS ),
							Roles::VIEW_INTEGRATIONS              => current_user_can( Roles::VIEW_INTEGRATIONS ),
							Roles::VIEW_LICENSE_KEY               => current_user_can( Roles::VIEW_LICENSE_KEY ),
							Roles::VIEW_EXPERIMENTAL_FEATURES     => current_user_can( Roles::VIEW_EXPERIMENTAL_FEATURES ),
							Roles::VIEW_TEST_MODE                 => current_user_can( Roles::VIEW_TEST_MODE ),
							Roles::VIEW_UNINSTALL                 => current_user_can( Roles::VIEW_UNINSTALL ),
							Roles::VIEW_USAGE_ANALYTICS           => current_user_can( Roles::VIEW_USAGE_ANALYTICS ),
						),
						'email_digest_notifications' => array(
							'email_digest_summary'         => array(
								'checked' => true,
								'name'    => 'email_digest_summary',
							),
							'notification_day'             => array(
								'options' => array(
									array(
										'label' => esc_html__( 'Every Day', 'gravitysmtp' ),
										'value' => 'daily',
									),
									array(
										'label' => esc_html__( 'Every Week', 'gravitysmtp' ),
										'value' => 'weekly',
									),
									array(
										'label' => esc_html__( 'Every Month', 'gravitysmtp' ),
										'value' => 'monthly',
									),
									array(
										'label' => esc_html__( 'Every Year', 'gravitysmtp' ),
										'value' => 'yearly',
									),
								),
								'name'    => 'notification_day',
							),
							'notification_email_addresses' => array(
								'value' => 'olivia@gravity.com, carl@hancock.io',
								'name'  => 'notification_email_addresses',
							),
							'enable_html_mode'             => array(
								'checked' => true,
								'name'    => 'enable_html_mode',
							),
						),
						'route_path'                 => admin_url( 'admin.php' ),
						'plugins_url'                => admin_url( 'plugins.php' ),
						'nav_item_param_key'         => 'tab',
						'nav_items'                  => array(
							array(
								'param' => 'settings',
								'label' => esc_html__( 'Settings', 'gravitysmtp' ),
								'icon'  => 'settings',
							),
							array(
								'param' => 'integrations',
								'label' => esc_html__( 'Integrations', 'gravitysmtp' ),
								'icon'  => 'cloud1',
							),
							array(
								'param' => 'alerts',
								'label' => esc_html__( 'Alerts', 'gravitysmtp' ),
								'icon'  => 'exclamation-triangle',
							),
							array(
								'param' => 'emails',
								'label' => esc_html__( 'Emails', 'gravitysmtp' ),
								'icon'  => 'mail',
							),
							array(
								'param' => 'logging',
								'label' => esc_html__( 'Logging', 'gravitysmtp' ),
								'icon'  => 'circle-tool',
							),
							array(
								'param' => 'routing',
								'label' => esc_html__( 'Routing', 'gravitysmtp' ),
								'icon'  => 'routing',
							),
						),
						'integrations_actions'       => array(
							0 =>
								array(
									'key'   => 'edit',
									'props' => array(
										'element'    => 'button',
										'iconBefore' => 'api',
										'iconPrefix' => 'gravitysmtp-admin-icon',
										'label'      => esc_html__( 'API Settings', 'gravitysmtp' ),
									),
								),
							1 =>
								array(
									'key'   => 'send-a-test',
									'props' => array(
										'customAttributes' => array(
											'href'         => admin_url( 'admin.php?page=gravitysmtp-tools&tab=send-a-test' ),
											'data-test-id' => 'send-a-test-action'
										),
										'element'          => 'link',
										'iconBefore'       => 'paper-plane',
										'iconPrefix'       => 'gravitysmtp-admin-icon',
										'label'            => esc_html__( 'Send A Test', 'gravitysmtp' ),
									),
								),
							2 =>
								array(
									'key'   => 'set-as-primary',
									'props' => array(
										'element'    => 'button',
										'iconBefore' => 'primary',
										'iconPrefix' => 'gravitysmtp-admin-icon',
										'label'      => esc_html__( 'Set As Primary', 'gravitysmtp' ),
									),
								),
							3 =>
								array(
									'key'   => 'set-as-backup',
									'props' => array(
										'element'    => 'button',
										'iconBefore' => 'circle-lightning-bolt',
										'iconPrefix' => 'gravitysmtp-admin-icon',
										'label'      => esc_html__( 'Set As Backup', 'gravitysmtp' ),
									),
								),
						),
						'email_notifications'        => array(
							array(
								'title'    => esc_html__( 'Change of Admin Email', 'gravitysmtp' ),
								'settings' => array(
									array(
										'label'   => esc_html__( 'Site Admin Email Change Attempt', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'site_admin_email_change_attempt',
									),
									array(
										'label'   => esc_html__( 'Site Admin Email Changed', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'site_admin_email_changed',
									),
								),
							),
							array(
								'title'    => esc_html__( 'Change of User Email or Password', 'gravitysmtp' ),
								'settings' => array(
									array(
										'label'   => esc_html__( 'Reset Password Request', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'reset_password_request',
									),
									array(
										'label'   => esc_html__( 'Password Reset Successfully', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'password_reset_successfully',
									),
									array(
										'label'   => esc_html__( 'Password Changed', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'password_changed',
									),
									array(
										'label'   => esc_html__( 'Email Change Attempt', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'email_change_attempt',
									),
									array(
										'label'   => esc_html__( 'Email Changed', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'email_changed',
									),
								),
							),
							array(
								'title'    => esc_html__( 'Personal Data Requests', 'gravitysmtp' ),
								'settings' => array(
									array(
										'label'   => esc_html__( 'User Confirmed Export / Erasure Request', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'user_confirmed_export_erasure_request',
									),
									array(
										'label'   => esc_html__( 'Admin Erased Data', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'admin_erased_data',
									),
									array(
										'label'   => esc_html__( 'Admin Sent Link to Export Data', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'admin_sent_link_to_export_data',
									),
								),
							),
							array(
								'title'    => esc_html__( 'Automatic Updates', 'gravitysmtp' ),
								'settings' => array(
									array(
										'label'   => esc_html__( 'Plugin Status', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'plugin_status',
									),
									array(
										'label'   => esc_html__( 'Theme Status', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'theme_status',
									),
									array(
										'label'   => esc_html__( 'WP Core Status', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'wp_core_status',
									),
									array(
										'label'   => esc_html__( 'Full Log', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'full_log',
									),
								),
							),
							array(
								'title'    => esc_html__( 'New User', 'gravitysmtp' ),
								'settings' => array(
									array(
										'label'   => esc_html__( 'Created (Admin)', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'created_admin',
									),
									array(
										'label'   => esc_html__( 'Created (User)', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'created_user',
									),
								),
							),
							array(
								'title'    => esc_html__( 'Comments', 'gravitysmtp' ),
								'settings' => array(
									array(
										'label'   => esc_html__( 'Awaiting Moderation', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'awaiting_moderation',
									),
									array(
										'label'   => esc_html__( 'Published', 'gravitysmtp' ),
										'checked' => false,
										'name'    => 'published',
									),
								),
							),
							array(
								'title'    => esc_html__( 'WooCommerce', 'gravitysmtp' ),
								'settings' => array(
									array(
										'label'   => esc_html__( 'Purchase Receipt', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'purchase_receipt',
									),
									array(
										'label'   => esc_html__( 'Password Change', 'gravitysmtp' ),
										'checked' => true,
										'name'    => 'password_change',
									),
								),
							),
						),
						'primary_locked'             => $primary_locked,
						'backup_locked'              => $backup_locked,
					),
					'endpoints' => array(),
				),
			)
		);
	}

	public function get_email_log_retention_options() {
		$options = array(
			array(
				'label' => esc_html__( '1 Day', 'gravitysmtp' ),
				'value' => 1,
			),
			array(
				'label' => esc_html__( '1 Week', 'gravitysmtp' ),
				'value' => 7,
			),
			array(
				'label' => esc_html__( '1 Month', 'gravitysmtp' ),
				'value' => 30,
			),
			array(
				'label' => esc_html__( '3 Months', 'gravitysmtp' ),
				'value' => 90,
			),
			array(
				'label' => esc_html__( '6 Months', 'gravitysmtp' ),
				'value' => 180,
			),
			array(
				'label' => esc_html__( '1 Year', 'gravitysmtp' ),
				'value' => 365,
			),
			array(
				'label' => esc_html__( 'Never Delete', 'gravitysmtp' ),
				'value' => 0,
			),
		);

		return apply_filters( 'gravitysmtp_email_log_retention_options', $options );
	}

	public function get_debug_log_retention_options() {
		$options = array(
			array(
				'label' => esc_html__( '1 Week', 'gravitysmtp' ),
				'value' => 7,
			),
			array(
				'label' => esc_html__( '1 Month', 'gravitysmtp' ),
				'value' => 30,
			),
		);

		return apply_filters( 'gravitysmtp_debug_log_retention_options', $options );
	}
}
