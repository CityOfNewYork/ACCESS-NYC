<?php

namespace Gravity_Forms\Gravity_SMTP\Connectors;

use Gravity_Forms\Gravity_SMTP\Apps\Config\Tools_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Config\Connector_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Config\Connector_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Check_Background_Tasks_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Cleanup_Data_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Get_Connector_Emails;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Get_Single_Email_Data_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Connector_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Send_Test_Endpoint;
use Gravity_Forms\Gravity_SMTP\Connectors\Oauth\Google_Oauth_Handler;
use Gravity_Forms\Gravity_SMTP\Connectors\Oauth\Microsoft_Oauth_Handler;
use Gravity_Forms\Gravity_SMTP\Connectors\Oauth\Zoho_Oauth_Handler;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Amazon;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Brevo;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Elastic_Email;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Generic;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Google;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Mailchimp;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Mailersend;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Mailgun;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Microsoft;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_PHPMail;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Postmark;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Sendgrid;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_SMTP2GO;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Sparkpost;
use Gravity_Forms\Gravity_SMTP\Connectors\Types\Connector_Zoho;
use Gravity_Forms\Gravity_SMTP\Data_Store\Const_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Log\Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Logging_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Models\Hydrators\Hydrator_Factory;
use Gravity_Forms\Gravity_SMTP\Models\Log_Details_Model;
use Gravity_Forms\Gravity_SMTP\Models\Notifications_Model;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Logging\DB_Logging_Provider;
use Gravity_Forms\Gravity_Tools\Providers\Config_Collection_Service_Provider;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Connector_Service_Provider extends Config_Service_Provider {

	const CONNECTOR_FACTORY      = 'connector_factory';
	const PHPMAILER              = 'phpmailer';
	const DATA_STORE_CONST       = 'data_store_const';
	const DATA_STORE_OPTS        = 'data_store_opts';
	const DATA_STORE_PLUGIN_OPTS = 'data_store_plugin_opts';
	const DATA_STORE_ROUTER      = 'data_store_router';
	const EVENT_MODEL            = 'event_model';
	const LOG_DETAILS_MODEL      = 'log_details_model';
	const NOTIFICATIONS_MODEL    = 'notifications_model';
	const HYDRATOR_FACTORY       = 'hydrator_factory';
	const NAME_MAP               = 'name_map';
	const REGISTERED_CONNECTORS  = 'registered_connectors';
	const CONNECTOR_DATA_MAP     = 'connector_data_map';

	const OAUTH_DATA_HANDLER      = 'oauth_data_handler';
	const GOOGLE_OAUTH_HANDLER    = 'google_oauth_handler';
	const MICROSOFT_OAUTH_HANDLER = 'microsoft_oauth_handler';
	const ZOHO_OAUTH_HANDLER      = 'zoho_oauth_handler';

	const SEND_TEST_ENDPOINT               = 'send_test_endpoint';
	const CLEANUP_DATA_ENDPOINT            = 'cleanup_data_endpoint';
	const SAVE_CONNECTOR_SETTINGS_ENDPOINT = 'save_connector_settings_endpoint';
	const SAVE_PLUGIN_SETTINGS_ENDPOINT    = 'save_plugin_settings_endpoint';
	const GET_SINGLE_EMAIL_DATA_ENDPOINT   = 'get_single_email_data_endpoint';
	const CHECK_BACKGROUND_TASKS_ENDPOINT  = 'check_background_tasks_endpoint';
	const GET_CONNECTOR_EMAILS_ENDPOINT    = 'get_connector_emails_endpoint';

	const CONNECTOR_ENDPOINTS_CONFIG = 'connector_endpoints_config';

	const CONNECTOR_AMAZON_SES    = 'Amazon';
	const CONNECTOR_BREVO         = 'Brevo';
	const CONNECTOR_ELASTIC_EMAIL = 'Elastic_Email';
	const CONNECTOR_GENERIC       = 'Generic';
	const CONNECTOR_GOOGLE        = 'Google';
	const CONNECTOR_MAILCHIMP     = 'Mailchimp';
	const CONNECTOR_MAILERSEND    = 'MailerSend';
	const CONNECTOR_MAILGUN       = 'Mailgun';
	const CONNECTOR_MAILJET       = 'Mailjet';
	const CONNECTOR_MICROSOFT     = 'Microsoft';
	const CONNECTOR_PHPMAIL       = 'Phpmail';
	const CONNECTOR_POSTMARK      = 'Postmark';
	const CONNECTOR_SENDGRID      = 'Sendgrid';
	const CONNECTOR_SMTP2GO       = 'SMTP2GO';
	const CONNECTOR_SPARKPOST     = 'Sparkpost';
	const CONNECTOR_ZOHO          = 'Zoho';

	protected $connectors = array(
		self::CONNECTOR_AMAZON_SES    => Connector_Amazon::class,
		self::CONNECTOR_BREVO         => Connector_Brevo::class,
		self::CONNECTOR_ELASTIC_EMAIL => Connector_Elastic_Email::class,
		self::CONNECTOR_GENERIC       => Connector_Generic::class,
		self::CONNECTOR_GOOGLE        => Connector_Google::class,
		self::CONNECTOR_MAILCHIMP     => Connector_Mailchimp::class,
		self::CONNECTOR_MAILERSEND    => Connector_Mailersend::class,
		self::CONNECTOR_MAILGUN       => Connector_Mailgun::class,
		self::CONNECTOR_MAILJET       => Connector_Mailjet::class,
		self::CONNECTOR_MICROSOFT     => Connector_Microsoft::class,
		self::CONNECTOR_PHPMAIL       => Connector_PHPMail::class,
		self::CONNECTOR_POSTMARK      => Connector_Postmark::class,
		self::CONNECTOR_SENDGRID      => Connector_Sendgrid::class,
		self::CONNECTOR_SMTP2GO       => Connector_SMTP2GO::class,
		self::CONNECTOR_SPARKPOST     => Connector_Sparkpost::class,
		self::CONNECTOR_ZOHO          => Connector_Zoho::class,
	);

	protected $configs = array(
		self::CONNECTOR_ENDPOINTS_CONFIG => Connector_Endpoints_Config::class,
	);

	public function register( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		parent::register( $container );

		$self = $this;

		$this->container->add( self::PHPMAILER, function () {
			global $phpmailer;

			// (Re)create it, if it's gone missing.
			if ( ! ( $phpmailer ) ) {
				if ( file_exists( ABSPATH . WPINC . '/PHPMailer/PHPMailer.php' ) ) {
					require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
					require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
					require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
					$phpmailer = new \PHPMailer\PHPMailer\PHPMailer( true );
				} elseif ( file_exists( ABSPATH . WPINC . '/class-phpmailer.php' ) ) {
					require_once ABSPATH . WPINC . '/class-phpmailer.php';
					require_once ABSPATH . WPINC . '/class-smtp.php';
					$phpmailer = new PHPMailer( true );
				}

				$phpmailer::$validator = static function ( $email ) {
					return (bool) is_email( $email );
				};
			}

			return $phpmailer;
		} );

		$this->container->add( self::HYDRATOR_FACTORY, function () {
			return new Hydrator_Factory();
		} );

		$this->container->add( self::DATA_STORE_CONST, function () {
			return new Const_Data_Store();
		} );

		$this->container->add( self::DATA_STORE_OPTS, function () {
			return new Opts_Data_Store();
		} );

		$this->container->add( self::DATA_STORE_PLUGIN_OPTS, function () {
			return new Plugin_Opts_Data_Store();
		} );

		$this->container->add( self::EVENT_MODEL, function () use ( $container ) {
			return new Event_Model( $container->get( self::HYDRATOR_FACTORY ), $container->get( self::DATA_STORE_PLUGIN_OPTS ), $container->get( Utils_Service_Provider::RECIPIENT_PARSER ), $container->get( Utils_Service_Provider::FILTER_PARSER ) );
		} );

		$this->container->add( self::LOG_DETAILS_MODEL, function () use ( $container ) {
			return new Log_Details_Model( $container->get( self::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$this->container->add( self::NOTIFICATIONS_MODEL, function () use ( $container ) {
			return new Notifications_Model();
		} );

		$this->container->add( Logging_Service_Provider::LOGGER, function () use ( $container ) {
			return new Logger( $container->get( self::LOG_DETAILS_MODEL ) );
		} );

		$this->container->add( Logging_Service_Provider::DEBUG_LOG_MODEL, function () use ( $container ) {
			return new Debug_Log_Model();
		} );

		$this->container->add( Logging_Service_Provider::DB_LOGGING_PROVIDER, function () use ( $container ) {
			return new DB_Logging_Provider( $container->get( Logging_Service_Provider::DEBUG_LOG_MODEL ) );
		} );

		$this->container->add( Logging_Service_Provider::DEBUG_LOGGER, function () use ( $container ) {
			$data      = $container->get( self::DATA_STORE_ROUTER );
			$log_level = $data->get_plugin_setting( Tools_Config::SETTING_DEBUG_LOG_LEVEL, DB_Logging_Provider::DEBUG );

			return new Debug_Logger( $container->get( Logging_Service_Provider::DB_LOGGING_PROVIDER ), $log_level );
		} );

		$this->container->add( self::DATA_STORE_ROUTER, function () use ( $container ) {
			return new Data_Store_Router( $container->get( self::DATA_STORE_CONST ), $container->get( self::DATA_STORE_OPTS ), $container->get( self::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$this->container->add( self::CONNECTOR_FACTORY, function () use ( $container ) {
			return new Connector_Factory(
				$container->get( self::PHPMAILER ),
				$container->get( self::DATA_STORE_ROUTER ),
				$container->get( Logging_Service_Provider::LOGGER ),
				$container->get( self::EVENT_MODEL ),
				$container->get( Utils_Service_Provider::HEADER_PARSER ),
				$container->get( Utils_Service_Provider::RECIPIENT_PARSER ),
				$container->get( Logging_Service_Provider::DEBUG_LOGGER )
			);
		} );

		$this->container->add( self::SAVE_CONNECTOR_SETTINGS_ENDPOINT, function () use ( $container ) {
			return new Save_Connector_Settings_Endpoint( $container->get( self::DATA_STORE_OPTS ), $container->get( self::DATA_STORE_PLUGIN_OPTS ), $container->get( self::CONNECTOR_FACTORY ) );
		} );

		$this->container->add( self::CLEANUP_DATA_ENDPOINT, function () use ( $container ) {
			return new Cleanup_Data_Endpoint( $container->get( self::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$this->container->add( self::SAVE_PLUGIN_SETTINGS_ENDPOINT, function () use ( $container ) {
			return new Save_Plugin_Settings_Endpoint( $container->get( self::DATA_STORE_PLUGIN_OPTS ), $container->get( Updates_Service_Provider::LICENSE_API_CONNECTOR ) );
		} );

		$this->container->add( self::GET_SINGLE_EMAIL_DATA_ENDPOINT, function () use ( $container ) {
			return new Get_Single_Email_Data_Endpoint( $container->get( self::LOG_DETAILS_MODEL ), $container->get( self::EVENT_MODEL ) );
		} );

		$this->container->add( self::CHECK_BACKGROUND_TASKS_ENDPOINT, function () {
			return new Check_Background_Tasks_Endpoint();
		} );

		$this->container->add( self::SEND_TEST_ENDPOINT, function () use ( $container ) {
			return new Send_Test_Endpoint( $container->get( self::CONNECTOR_FACTORY ), $container->get( self::DATA_STORE_ROUTER ), $container->get( self::EVENT_MODEL ), $container->get( self::LOG_DETAILS_MODEL ), $container->get( self::GET_SINGLE_EMAIL_DATA_ENDPOINT ) );
		} );

		$this->container->add( self::GET_CONNECTOR_EMAILS_ENDPOINT, function () use ( $container ) {
			return new Get_Connector_Emails( $container->get( self::NOTIFICATIONS_MODEL ) );
		} );

		$this->container->add( self::OAUTH_DATA_HANDLER, function () use ( $container ) {
			return new Oauth_Data_Handler( $container->get( self::DATA_STORE_ROUTER ), $container->get( self::DATA_STORE_OPTS ) );
		} );

		$this->container->add( self::GOOGLE_OAUTH_HANDLER, function () use ( $container ) {
			return new Google_Oauth_Handler( $container->get( self::OAUTH_DATA_HANDLER ) );
		} );

		$this->container->add( self::MICROSOFT_OAUTH_HANDLER, function () use ( $container ) {
			return new Microsoft_Oauth_Handler( $container->get( self::OAUTH_DATA_HANDLER ) );
		} );

		$this->container->add( self::ZOHO_OAUTH_HANDLER, function () use ( $container ) {
			return new Zoho_Oauth_Handler( $container->get( self::OAUTH_DATA_HANDLER ) );
		} );

		$this->container->add( self::REGISTERED_CONNECTORS, function () use ( $self ) {
			return $self->connectors;
		} );

		$this->register_connector_data();
	}

	public function init( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		add_action( 'init', function () use ( $container ) {
			$page = filter_input( INPUT_GET, 'page' );

			if ( $page !== 'gravitysmtp-settings' ) {
				return;
			}

			$connectors = $container->get( self::REGISTERED_CONNECTORS );

			foreach ( $connectors as $service => $class ) {
				$configured_key = sprintf( 'gsmtp_connector_configured_%s', strtolower( $service ) );
				delete_transient( $configured_key );
			}
		}, 11 );

		// @todo - replace this with some AJAX action via JS
		add_action( 'admin_post_smtp_disconnect_google', function () use ( $container ) {
			$configured_key = sprintf( 'gsmtp_connector_configured_%s', 'google' );

			delete_transient( $configured_key );

			/**
			 * @var Opts_Data_Store $data
			 */
			$data = $container->get( self::DATA_STORE_OPTS );

			/**
			 * @var Data_Store_Router
			 */
			$data_router = $container->get( self::DATA_STORE_ROUTER );

			/**
			 * @var Plugin_Opts_Data_Store
			 */
			$plugin_data_store = $container->get( self::DATA_STORE_PLUGIN_OPTS );

			$data->delete_all( 'google' );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['google'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, $connector_values );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['google'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR, $connector_values );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['google'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, $connector_values );

			/**
			 * @var Google_Oauth_Handler $oauth_handler
			 */
			$oauth_handler = $container->get( self::GOOGLE_OAUTH_HANDLER );
			$return_url    = urldecode( $oauth_handler->get_return_url( false ) );

			wp_safe_redirect( $return_url );
		} );

		add_action( 'admin_post_smtp_disconnect_microsoft', function () use ( $container ) {
			$configured_key = sprintf( 'gsmtp_connector_configured_%s', 'microsoft' );

			delete_transient( $configured_key );

			/**
			 * @var Opts_Data_Store $data
			 */
			$data = $container->get( self::DATA_STORE_OPTS );

			/**
			 * @var Data_Store_Router
			 */
			$data_router = $container->get( self::DATA_STORE_ROUTER );

			/**
			 * @var Plugin_Opts_Data_Store
			 */
			$plugin_data_store = $container->get( self::DATA_STORE_PLUGIN_OPTS );

			$data->delete_all( 'microsoft' );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['microsoft'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, $connector_values );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['microsoft'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR, $connector_values );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['microsoft'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, $connector_values );

			/**
			 * @var Microsoft_Oauth_Handler $oauth_handler
			 */
			$oauth_handler = $container->get( self::MICROSOFT_OAUTH_HANDLER );
			$return_url    = urldecode( $oauth_handler->get_return_url( 'settings' ) );

			wp_safe_redirect( $return_url );
		} );

		add_action( 'admin_post_smtp_disconnect_zoho', function () use ( $container ) {
			$configured_key = sprintf( 'gsmtp_connector_configured_%s', 'zoho' );

			delete_transient( $configured_key );

			/**
			 * @var Opts_Data_Store $data
			 */
			$data = $container->get( self::DATA_STORE_OPTS );

			/**
			 * @var Data_Store_Router
			 */
			$data_router = $container->get( self::DATA_STORE_ROUTER );

			/**
			 * @var Plugin_Opts_Data_Store
			 */
			$plugin_data_store = $container->get( self::DATA_STORE_PLUGIN_OPTS );

			$data->delete_all( 'zoho' );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['zoho'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_PRIMARY_CONNECTOR, $connector_values );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['zoho'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_BACKUP_CONNECTOR, $connector_values );

			$connector_values = $data_router->get_plugin_setting( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, array() );

			if ( ! is_array( $connector_values ) ) {
				$connector_values = array();
			}

			$connector_values['zoho'] = 'false';
			$plugin_data_store->save( Save_Connector_Settings_Endpoint::SETTING_ENABLED_CONNECTOR, $connector_values );

			/**
			 * @var Zoho_Oauth_Handler $oauth_handler
			 */
			$oauth_handler = $container->get( self::ZOHO_OAUTH_HANDLER );
			$return_url    = urldecode( $oauth_handler->get_return_url( false ) );

			wp_safe_redirect( $return_url );
		} );

		add_action( 'wp_ajax_' . Cleanup_Data_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::CLEANUP_DATA_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Send_Test_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::SEND_TEST_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Save_Connector_Settings_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::SAVE_CONNECTOR_SETTINGS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Save_Plugin_Settings_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::SAVE_PLUGIN_SETTINGS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Get_Single_Email_Data_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::GET_SINGLE_EMAIL_DATA_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Check_Background_Tasks_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::CHECK_BACKGROUND_TASKS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Get_Connector_Emails::ACTION_NAME, function () use ( $container ) {
			$container->get( self::GET_CONNECTOR_EMAILS_ENDPOINT )->handle();
		} );

		add_filter( 'gform_localized_script_data_gravitysmtp_admin_config', function ( $data ) {
			if (
				empty( $data['components']['settings']['data']['integrations'] ) &&
				empty( $data['components']['setup_wizard']['data']['integrations'] ) &&
				empty( $data['components']['tools']['data']['integrations'] )
			) {
				return $data;
			}

			$order = array(
				'amazon-ses',
				'brevo',
				'elastic_email',
				'generic',
				'google-gmail',
				'mailchimp',
				'mailersend',
				'mailgun',
				'microsoft',
				'phpmail',
				'postmark',
				'sendgrid',
				'smtp2go',
				'sparkpost',
				'zoho-mail',
			);

			// todo: setup wizard data should only be injected if should display is true for the app: includes/apps/setup-wizard/config/class-setup-wizard-config.php:18
			foreach ( array( 'settings', 'setup_wizard', 'tools' ) as $app ) {
				if ( empty( $data['components'][ $app ]['data']['integrations'] ) ) {
					continue;
				}

				$integrations = $data['components'][ $app ]['data']['integrations'];

				usort( $integrations, function ( $a, $b ) use ( $order ) {
					$a_pos = array_search( $a['id'], $order );
					$b_pos = array_search( $b['id'], $order );

					if ( $a_pos === $b_pos ) {
						return 0;
					}

					return $a_pos < $b_pos ? - 1 : 1;
				} );

				$data['components'][ $app ]['data']['integrations'] = $integrations;
			}

			return $data;
		} );
	}

	private function register_connector_data() {
		$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		$page = filter_input( INPUT_GET, 'page' );

		if ( ! $is_ajax && ! is_string( $page ) ) {
			return;
		}

		if ( ! empty( $page ) ) {
			$page = htmlspecialchars( $page );
		}

		if ( is_null( $page ) ) {
			$page = '';
		}

		$plugin_data_store = $this->container->get( self::DATA_STORE_PLUGIN_OPTS );
		$should_display    = Booliesh::get( $plugin_data_store->get( Save_Plugin_Settings_Endpoint::PARAM_SETUP_WIZARD_SHOULD_DISPLAY, 'config', 'true' ) );

		$should_register = $should_display ? strpos( $page, 'gravitysmtp-' ) !== false : in_array( $page, array(
			'gravitysmtp-activity-log',
			'gravitysmtp-dashboard',
			'gravitysmtp-settings',
			'gravitysmtp-suppression',
			'gravitysmtp-tools',
		) );

		if ( $is_ajax ) {
			$action = filter_input( INPUT_POST, 'action' );

			if ( $action === 'migrate_settings' || $action === 'get_dashboard_data' ) {
				$should_register = true;
			}
		}

		if ( empty( $should_register ) ) {
			return;
		}

		$connectors        = apply_filters( 'gravitysmtp_connector_types', $this->connectors );
		$config_collection = $this->container->get( Config_Collection_Service_Provider::CONFIG_COLLECTION );
		$parser            = $this->container->get( Config_Collection_Service_Provider::DATA_PARSER );

		/**
		 * @var Connector_Factory $factory
		 */
		$factory  = $this->container->get( self::CONNECTOR_FACTORY );
		$name_map = array();
		$data_map = array();

		foreach ( $connectors as $connector_name => $connector ) {
			$instance       = $factory->create( $connector_name );
			$config         = new Connector_Config( $parser );
			$connector_data = $instance->get_data();
			$config->set_data( $connector_data );
			$config_collection->add_config( $config );

			$name_map[ $connector_data['name'] ] = $connector_data['title'];
			$data_map[ $connector_data['name'] ] = $connector_data;
		}

		$this->container->add( self::NAME_MAP, $name_map );
		$this->container->add( self::CONNECTOR_DATA_MAP, $data_map );
	}
}
