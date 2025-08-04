<?php

namespace Gravity_Forms\Gravity_SMTP;

use Gravity_Forms\Gravity_SMTP\Alerts\Alerts_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Apps\Setup_Wizard\Setup_Wizard_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Assets\Assets_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Const_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Email_Management\Email_Management_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Environment\Environment_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Experimental_Features\Experimental_Features_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flag_Manager;
use Gravity_Forms\Gravity_SMTP\Feature_Flags\Feature_Flags_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Errors\Error_Handler_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Handler\Handler_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Migration\Migration_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Pages\Page_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Routing\Routing_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Suppression\Suppression_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Telemetry\Telemetry_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Tracking\Tracking_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Translations\Translations_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Users\Users_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Providers\Config_Collection_Service_Provider;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;
use Gravity_Forms\Gravity_Tools\Upgrades\Upgrade_Routines;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

/**
 * Loads Gravity SMTP.
 *
 * @since 1.0
 */
class Gravity_SMTP {

	/**
	 * @var Service_Container $container
	 */
	public static $container;

	public static function pre_init() {
		self::handle_feature_flags();
		self::load_early_providers();
	}

	/**
	 * Loads the required files.
	 *
	 * @since  1.0
	 */
	public static function load_plugin() {
		self::clear_cache_for_oauth();

		self::load_providers();
	}

	/**
	 * Run upgrade routines on plugins_loaded to ensure users have the most-up-to-date system when updating.
	 *
	 * @return void
	 */
	public static function run_upgrade_routines() {
		// Allow upgrades to be skipped if needed.
		if ( defined( 'GRAVITYSMTP_SKIP_UPGRADE_CHECK' ) && GRAVITYSMTP_SKIP_UPGRADE_CHECK ) {
			return;
		}

		$routines = new Upgrade_Routines( 'gravitysmtp' );

		// Ensure tables are set up properly
		$routines->add( 'emails_tables', array( self::class, 'create_emails_tables' ) );

		// Ensure a primary connection exists
		$routines->add( 'primary_connection', array( self::class, 'set_primary_connection' ) );

		// Ensure rewrite rules are flushed for tracking.
		$routines->add( 'flush_rewrite_rules', array( self::class, 'flush_rewrite_rules' ) );

		// Ensure tracking table is set up properly
		$routines->add( 'tracking_tables', array( self::class, 'create_tracking_tables' ) );

		// Ensure suppression table is set up properly
		$routines->add( 'suppression_tables', array( self::class, 'create_suppression_table' ) );

		// Ensure users who had tracking enabled previously have that feature migrated on update.
		$routines->add( 'enabled_tracking_experimental_migration', array( self::class, 'migrate_enabled_tracking_to_experimental' ) );
		add_action( 'plugins_loaded', function() use ( $routines ) {
			$routines->handle();
		}, 10 );
	}

	private static function clear_cache_for_oauth() {
		$payload = filter_input( INPUT_POST, 'auth_payload' );

		if ( ! empty( $payload ) ) {
			$configured_key = sprintf( 'gsmtp_connector_configured_%s', 'google' );
			delete_transient( $configured_key );
		}
	}

	public static function set_primary_connection() {
		$const  = new Const_Data_Store();
		$opts   = new Opts_Data_Store();
		$plugin = new Plugin_Opts_Data_Store();
		$router = new Data_Store_Router( $const, $opts, $plugin );

		$primaries = $router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, array() );
		$selected  = array_filter( $primaries );

		if ( ! empty( $selected ) ) {
			return;
		}

		$enabled  = $router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, array() );
		$selected = array_filter( $enabled );

		if ( empty( $selected ) ) {
			return;
		}

		$keys                            = array_keys( $selected );
		$enabled_connector               = reset( $keys );
		$primaries[ $enabled_connector ] = true;
		$opts->save( Connector_Base::SETTING_IS_PRIMARY, true, $enabled_connector );

		$plugin->save( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, $primaries );
	}

	public static function flush_rewrite_rules() {
		flush_rewrite_rules( true );
	}

	public static function create_emails_tables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$table_name = $wpdb->prefix . 'gravitysmtp_events';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
			    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    status varchar(100) NOT NULL,
			    service varchar(100) NOT NULL,
			    subject varchar(100) NOT NULL,
			    message text NOT NULL,
			    extra mediumtext NOT NULL,
			    PRIMARY KEY (id)
		    ) $charset_collate;
		";

		dbDelta( $sql );

		$log_table_name = $wpdb->prefix . 'gravitysmtp_event_logs';

		$sql = "
			CREATE TABLE $log_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
                event_id mediumint(9) NOT NULL,
			    action_name varchar(100) NOT NULL,
			    log_value text NOT NULL,
			    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    PRIMARY KEY (id)
		    ) $charset_collate;
		";

		dbDelta( $sql );

		$debug_log_table_name = $wpdb->prefix . 'gravitysmtp_debug_log';

		$sql = "
			CREATE TABLE $debug_log_table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
			    priority varchar(100) NOT NULL,
			    line text NOT NULL,
			    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    date_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    PRIMARY KEY (id)
		    ) $charset_collate;
		";

		dbDelta( $sql );
	}

	public static function create_tracking_tables() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$table_name        = $wpdb->prefix . 'gravitysmtp_event_tracking';
		$charset_collate   = $wpdb->get_charset_collate();
		$events_table_name = $wpdb->prefix . 'gravitysmtp_events';

		$sql = "
			CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				event_id mediumint(9) NOT NULL,
			    email varchar(100) NOT NULL,
			    opened tinyint(1) NOT NULL,
			    clicked tinyint(1) NOT NULL,
			    PRIMARY KEY (id),
			    FOREIGN KEY (event_id) REFERENCES $events_table_name(id)
		    ) $charset_collate;
		";

		dbDelta( $sql );
	}

	public static function create_suppression_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$table_name        = $wpdb->prefix . 'gravitysmtp_suppressed_emails';
		$charset_collate   = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
			    email varchar(100) NOT NULL,
			    reason varchar(100) NOT NULL,
			    notes text,
			    date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			    PRIMARY KEY (id),
			    FULLTEXT(email, notes)
		    ) $charset_collate;
		";

		dbDelta( $sql );
	}

	/**
	 * Get a list of all custom table names for the plugin.
	 *
	 * @since 1.9.5
	 *
	 * @return string[]
	 */
	public static function get_table_names() {
		return array(
			'gravitysmtp_events',
			'gravitysmtp_event_logs',
			'gravitysmtp_debug_log',
			'gravitysmtp_event_tracking',
			'gravitysmtp_suppressed_emails',
		);
	}

	/**
	 * If a user previously had open tracking enabled before it was experimental, update
	 * the experimental setting to be enabled on migration.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public static function migrate_enabled_tracking_to_experimental() {
		$settings = get_option( 'gravitysmtp_config' );
		$settings = json_decode( $settings, true );

		$open_tracking = isset( $settings['open_tracking'] ) ? $settings['open_tracking'] : false;
		$open_tracking = Booliesh::get( $open_tracking );

		if ( ! $open_tracking ) {
			return;
		}

		$experiments = isset( $settings['enabled_experimental_features'] ) ? $settings['enabled_experimental_features']  : array();
		$experiments['email_open_tracking'] = true;
		$settings['enabled_experimental_features'] = $experiments;

		update_option( 'gravitysmtp_config', json_encode( $settings ) );
	}

	public static function container() {
		if ( is_null( self::$container ) ) {
			self::load_providers();
		}

		return self::$container;
	}

	/**
	 * Enable Feature Flags once they are ready for production.
	 *
	 * @return void
	 */
	public static function handle_feature_flags() {
		// Enable Email Management Feature
		add_action( 'plugins_loaded', function() {
			Feature_Flag_Manager::add( 'wp_email_management', 'WP Email Management' );
			Feature_Flag_Manager::enable_flag( 'wp_email_management' );

			Feature_Flag_Manager::add( 'gravitysmtp_dashboard_app', 'Dashboard App Screen' );
			Feature_Flag_Manager::enable_flag( 'gravitysmtp_dashboard_app' );

			Feature_Flag_Manager::add( 'amazon_ses_integration', 'Amazon SES Integration' );
			Feature_Flag_Manager::enable_flag( 'amazon_ses_integration' );

			Feature_Flag_Manager::add( 'gravityforms_entry_note', 'Gravity Forms Entry Note' );
			Feature_Flag_Manager::enable_flag( 'gravityforms_entry_note' );

			Feature_Flag_Manager::add( 'mailchimp_integration', 'Mailchimp Integration' );
			Feature_Flag_Manager::enable_flag( 'mailchimp_integration' );

			Feature_Flag_Manager::add( 'email_open_tracking', 'Email Open Tracking' );
//			Feature_Flag_Manager::enable_flag( 'email_open_tracking' );

			Feature_Flag_Manager::add( 'email_suppression', 'Email Suppression' );
			Feature_Flag_Manager::enable_flag( 'email_suppression' );

			Feature_Flag_Manager::add( 'zoho_integration', 'Zoho Integration' );
			Feature_Flag_Manager::enable_flag( 'zoho_integration' );

			Feature_Flag_Manager::add( 'experimental_features_setting', 'Experimental Features Setting' );
			Feature_Flag_Manager::enable_flag( 'experimental_features_setting' );

			Feature_Flag_Manager::add( 'alerts_management', 'Alerts Management' );

			Feature_Flag_Manager::add( 'mailersend_integration', 'MailerSend Integration' );
			Feature_Flag_Manager::enable_flag( 'mailersend_integration' );

			Feature_Flag_Manager::add( 'elasticemail_integration', 'Elastic Email Integration' );
			Feature_Flag_Manager::enable_flag( 'elasticemail_integration' );

			Feature_Flag_Manager::add( 'smtp2go_integration', 'SMTP2GO Integration' );
			Feature_Flag_Manager::enable_flag( 'smtp2go_integration' );

			Feature_Flag_Manager::add( 'mailjet_integration', 'Mailjet Integration' );
			Feature_Flag_Manager::enable_flag( 'mailjet_integration' );

			Feature_Flag_Manager::add( 'sparkpost_integration', 'SparkPost Integration' );
			Feature_Flag_Manager::enable_flag( 'sparkpost_integration' );
		} );
	}

	public static function load_early_providers() {
		$full_path = __FILE__;

		self::$container = new Service_Container();
		self::$container->add_provider( new Users_Service_Provider() );
		self::$container->add_provider( new Utils_Service_Provider() );
		self::$container->add_provider( new Updates_Service_Provider( $full_path ) );
		self::$container->add_provider( new Translations_Service_Provider() );
		self::$container->add_provider( new Config_Collection_Service_Provider( 'gravitysmtp/v1' ) );
		self::$container->add_provider( new Feature_Flags_Service_Provider() );
	}

	protected static function load_providers() {
		if ( is_null( self::$container ) ) {
			self::load_early_providers();
		}

		// Has already initialized.
		if ( ! empty( self::$container->get( Connector_Service_Provider::EVENT_MODEL ) ) ) {
			return;
		}

		if ( Feature_Flag_Manager::is_enabled( 'email_suppression' ) ) {
			self::$container->add_provider( new Suppression_Service_Provider() );
		}

		// Common Providers
		self::$container->add_provider( new Connector_Service_Provider() );
		self::$container->add_provider( new Assets_Service_Provider( self::get_base_url(), self::get_local_dev_base_url(), self::get_base_dir() ) );
		self::$container->add_provider( new App_Service_Provider( self::get_base_url() ) );
		self::$container->add_provider( new Logging_Service_Provider() );

		if ( Feature_Flag_Manager::is_enabled( 'experimental_features_setting' ) ) {
			self::$container->add_provider( new Experimental_Features_Service_Provider() );
		}

		self::$container->add_provider( new Handler_Service_Provider() );
		self::$container->add_provider( new Page_Service_Provider( self::get_base_url() ) );
		self::$container->add_provider( new Setup_Wizard_Service_Provider() );
		self::$container->add_provider( new Telemetry_Service_Provider() );
		self::$container->add_provider( new Environment_Service_Provider() );
		self::$container->add_provider( new Routing_Service_Provider() );
		self::$container->add_provider( new Migration_Service_Provider() );
		self::$container->add_provider( new Error_Handler_Service_Provider() );

		if ( Feature_Flag_Manager::is_enabled( 'wp_email_management' ) ) {
			self::$container->add_provider( new Email_Management_Service_Provider() );
		}

		if ( Feature_Flag_Manager::is_enabled( 'alerts_management' ) ) {
			self::$container->add_provider( new Alerts_Service_Provider() );
		}

		if ( Feature_Flag_Manager::is_enabled( 'email_open_tracking' ) ) {
			self::$container->add_provider( new Tracking_Service_Provider() );
		}
	}

	public static function get_base_url() {
		return plugins_url( '', dirname( __FILE__ ) );
	}

	public static function get_base_dir() {
		return plugin_dir_path( dirname( __FILE__ ) );
	}

	public static function get_local_dev_base_url() {
		$url = self::get_base_url();

		if ( ! defined( 'GRAVITYSMTP_ENABLE_HMR' ) || ! GRAVITYSMTP_ENABLE_HMR ) {
			return $url . '/assets/js/dist';
		}

		$config = dirname( dirname( __FILE__ ) ) . '/local-config.json';

		if ( ! file_exists( $config ) ) {
			return $url . '/assets/js/dist';
		}

		// Get port info from local-config.json
		$json = file_get_contents( $config );
		$data = json_decode( $json, true );
		$port = isset( $data['hmr_port'] ) ? $data['hmr_port'] : '9003';

		// Set up the base URL and path.
		$base   = parse_url( $url, PHP_URL_HOST );
		$scheme = parse_url( $url, PHP_URL_SCHEME );

		return sprintf( '%s://%s:%s', $scheme, $base, $port );
	}

	public static function activation_hook() {
		self::load_providers();
		self::create_emails_tables();
		do_action( 'gravitysmtp_post_activation' );
	}

}
