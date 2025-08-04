<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Config;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Config;
use Gravity_Forms\Gravity_Tools\License\License_Statuses;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Setup_Wizard_Config extends Config {

	protected $script_to_localize = 'gravitysmtp_scripts_admin';
	protected $name               = 'gravitysmtp_admin_config';
	protected $overwrite          = false;

	public function should_enqueue() {
		$should_enqueue = Gravity_SMTP::container()->get( App_Service_Provider::SHOULD_ENQUEUE_SETUP_WIZARD );

		return is_callable( $should_enqueue ) ? $should_enqueue() : $should_enqueue;
	}

	public function data() {
		$container         = Gravity_SMTP::container();
		$plugin_data_store = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );

		$rg_forms_key    = get_option( 'rg_gforms_key', '' );
		$smtp_plugin_key = $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY, '' );

		$license_key = ! empty( $smtp_plugin_key ) ? $smtp_plugin_key : $rg_forms_key;
		$is_valid    = null;

		if ( ! empty( $license_key ) ) {
			$license_info = $container->get( Updates_Service_Provider::LICENSE_API_CONNECTOR )->check_license( $license_key );
			$is_valid     = License_Statuses::VALID_KEY === $license_info->get_status();
		}

		// should display logic check
		$should_display    = Booliesh::get( $plugin_data_store->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_SETUP_WIZARD_SHOULD_DISPLAY, 'true' ) );
		$setup_wizard_page = filter_input( INPUT_GET, 'setup-wizard-page' );
		$is_wizard_open    = $should_display;

		if ( is_string( $setup_wizard_page ) ) {
			$setup_wizard_page = htmlspecialchars( $setup_wizard_page );
			$is_wizard_open    = true;
		} else {
			$setup_wizard_page = 1;
		}

		$connectors_to_migrate = $container->get( Utils_Service_Provider::IMPORT_DATA_CHECKER )->connectors_to_migrate();

		return array(
			'components' => array(
				'setup_wizard' => array(
					'i18n' => array(
						'debug_messages' => array(
							/* translators: %1$s is the request data. */
							'checking_license_key'           => esc_html__( 'Setup Wizard Screen 01: Checking license key: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error data. */
							'checking_license_key_error'     => esc_html__( 'Setup Wizard Screen 01: Error checking license key: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the request data. */
							'migrating_data'                 => esc_html__( 'Setup Wizard Screen 02: Migrating data: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error data. */
							'migrating_data_error'           => esc_html__( 'Setup Wizard Screen 02: Error migrating data: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the request data. */
							'saving_license_key'             => esc_html__( 'Setup Wizard Screen 01: Saving license key: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error data. */
							'saving_license_key_error'       => esc_html__( 'Setup Wizard Screen 01: Error saving license key: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error data. */
							'saving_should_display_error'    => esc_html__( 'Setup Wizard Screen 01: Error saving should display: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the settings data. */
							'starting_first_run'             => esc_html__( 'Setup Wizard Screen 01: Starting wizard for first run: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the settings data. */
							'starting_subsequent_run'        => esc_html__( 'Setup Wizard Screen 01: Starting wizard from the settings screen: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the request data. */
							'setting_should_display_false'   => esc_html__( 'Setup Wizard Screen 01: Setting should display to false: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the request data. */
							'updating_usage_analytics'       => esc_html__( 'Setup Wizard Screen 01: Updating usage analytics: %1$s', 'gravitysmtp' ),
							/* translators: %1$s is the error data. */
							'updating_usage_analytics_error' => esc_html__( 'Setup Wizard Screen 01: Error updating usage analytics: %1$s', 'gravitysmtp' ),
						),
						'setup_wizard_welcome_title'               => esc_html__( 'Welcome to Gravity SMTP', 'gravitysmtp' ),
						'setup_wizard_welcome_copy'                => __( "You're only minutes away from sending emails with confidence! Use the setup wizard to get started if this is your first time using Gravity SMTP.", 'gravitysmtp' ),
						'setup_wizard_next'                        => esc_html__( 'Next', 'gravitysmtp' ),
						'setup_wizard_welcome_list_01'             => esc_html__( 'The Most Popular WordPress SMTP Plugin', 'gravitysmtp' ),
						'setup_wizard_welcome_list_02'             => __( 'Advanced Open & Click Tracking', 'gravitysmtp' ),
						'setup_wizard_welcome_list_03'             => esc_html__( 'Weekly Email Summaries', 'gravitysmtp' ),
						'setup_wizard_welcome_list_04'             => esc_html__( 'Text Alerts For Service Interruptions', 'gravitysmtp' ),
						'setup_wizard_get_started'                 => __( 'Let\'s Get Started', 'gravitysmtp' ),
						'setup_wizard_verify_license'              => esc_html__( 'Verify License', 'gravitysmtp' ),
						'setup_wizard_checking_license'            => esc_html__( 'Checking License', 'gravitysmtp' ),
						'setup_wizard_license_input_label'         => esc_html__( 'Enter License Key', 'gravitysmtp' ),
						'setup_wizard_license_input_placeholder'   => esc_html__( 'Enter your license key here', 'gravitysmtp' ),
						'setup_wizard_close_button'                => esc_html__( 'Close', 'gravitysmtp' ),
						'setup_wizard_back_button'                 => esc_html__( 'Back', 'gravitysmtp' ),
						'setup_wizard_skip_button'                 => esc_html__( 'Skip This Step', 'gravitysmtp' ),
						'setup_wizard_next_button'                 => esc_html__( 'Next', 'gravitysmtp' ),
						'setup_wizard_dashboard_button'            => esc_html__( 'Return to Dashboard', 'gravitysmtp' ),
						'setup_wizard_import_data_title'           => esc_html__( 'Migrate Your SMTP Settings', 'gravitysmtp' ),
						'setup_wizard_import_data_copy'            => __( "Gravity SMTP is compatible with multiple SMTP plugins. We’ve detected other SMTP plugins installed on your website. Choose which plugin’s data you want to import to Gravity SMTP for a seamless migration experience.", 'gravitysmtp' ),
						'setup_wizard_integration_title'           => esc_html__( 'Choose Your SMTP Mail Solution', 'gravitysmtp' ),
						'setup_wizard_integration_copy_1'          => esc_html__( 'Which SMTP mail solution would you like to use to send emails?', 'gravitysmtp' ),
						'setup_wizard_integration_copy_2'          => esc_html__( 'Not sure which to choose? Check out our Ultimate Gravity SMTP Guide for details on each option.', 'gravitysmtp' ),
						'setup_wizard_mail_settings_title'         => esc_html__( 'Configure Mail Settings', 'gravitysmtp' ),
						/* translators: %s: integration name. */
						'setup_wizard_mail_settings_copy'          => esc_html__( 'Get Started With %s', 'gravitysmtp' ),
						'setup_wizard_setup_skipped_title'         => esc_html__( 'Setup Skipped', 'gravitysmtp' ),
						'setup_wizard_setup_skipped_copy'          => __( "Congratulations, you're ready to start reliably and securely sending email from your site using Gravity SMTP. Head on over to the plugin dashboard to check out all the features Gravity SMTP has to offer.", 'gravitysmtp' ),
						'setup_wizard_setup_success_title'         => esc_html__( 'Setup Complete', 'gravitysmtp' ),
						'setup_wizard_setup_success_copy'          => __( "Congratulations, you're ready to start reliably and securely sending email from your site using Gravity SMTP. Head on over to the plugin dashboard to check out all the features Gravity SMTP has to offer.", 'gravitysmtp' ),
						'setup_wizard_setup_failed_title'          => __( "Whoops, looks like things aren't configured properly.", 'gravitysmtp' ),
						'setup_wizard_setup_failed_copy'           => esc_html__( 'We just tried to send a test email, but something prevented that from working. To see more details about the issue we detected, as well as our suggestions to fix it, please start troubleshooting.', 'gravitysmtp' ),
						'setup_wizard_integration_settings_error'  => esc_html__( 'There was an error saving your settings', 'gravitysmtp' ),
						/* translators: %s: integration name. */
						'setup_wizard_activate_integration_button' => esc_html__( 'Activate %s Integration', 'gravitysmtp' ),
					),
					'data' => array(
						'admin_url'         => array(
							'default' => '',
							'value'   => admin_url(),
						),
						'integrations_url'  => array(
							'default' => '',
							'value'   => admin_url( 'admin.php?page=gravitysmtp-settings&tab=integrations' ),
						),
						'connectors_to_migrate' => array(
							'default' => array(),
							'value'   => $connectors_to_migrate,
						),
						'defaults' => array(
							'activeNavBarStep' => array(
								'default' => 1,
								'value'   => (int) $setup_wizard_page,
							),
							'activeStep'       => array(
								'default' => 1,
								'value'   => (int) $setup_wizard_page,
							),
							'isOpen'           => array(
								'default' => true,
								'value'   => $is_wizard_open,
							),
						),
						'import_data' => array(
							array(
								'logo'      => 'GravityFormsStacked',
								'title'     => 'Gravity Forms',
								'id'        => 'gravityforms',
								'activated' => true,
							),
							array(
								'logo'           => 'WPMailSMTPFull',
								'title'          => 'WP Mail SMTP',
								'id'             => 'wpmailsmtp',
								'activated'      => true,
								'initialChecked' => in_array( 'wpmailsmtp', $connectors_to_migrate ), // @aaron set to true for one of these to make it checked by default
							),
						),
						'integrations'        => array(
							'default' => array(),
							'value'   => array(),
						),
						'license_key'         => array(
							'default' => '',
							'value'   => $is_valid ? $license_key : '',
						),
						'license_key_is_valid'  => array(
							'default' => false,
							'value'   => $is_valid,
						),
						'should_display'  => array(
							'default' => true,
							'value'   => $should_display,
						),
					),
				)
			)
		);
	}

}
