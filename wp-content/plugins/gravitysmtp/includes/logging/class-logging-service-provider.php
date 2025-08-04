<?php

namespace Gravity_Forms\Gravity_SMTP\Logging;

use Gravity_Forms\Gravity_SMTP\Apps\App_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Apps\Config\Tools_Config;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Data_Store\Const_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Handler\Mail_Handler;
use Gravity_Forms\Gravity_SMTP\Logging\Config\Logging_Endpoints_Config;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Log_Event_Handler;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Debug_Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Delete_Debug_Logs_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Delete_Email_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Delete_Events_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Get_Email_Message_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Get_Paginated_Debug_Log_Items_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Get_Paginated_Items_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\Log_Item_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Endpoints\View_Log_Endpoint;
use Gravity_Forms\Gravity_SMTP\Logging\Log\Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Log\WP_Mail_Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Scheduling\Handler;
use Gravity_Forms\Gravity_SMTP\Utils\Attachments_Saver;
use Gravity_Forms\Gravity_Tools\Providers\Config_Service_Provider;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Models\Debug_Log_Model;
use Gravity_Forms\Gravity_Tools\Logging\DB_Logging_Provider;
use Gravity_Forms\Gravity_Tools\Logging\Parsers\File_Log_Parser;

class Logging_Service_Provider extends Config_Service_Provider {

	const LOGGER                                 = 'logger';
	const WP_MAIL_LOGGER                         = 'wp_mail_logger';
	const GET_PAGINATED_ITEMS_ENDPOINT           = 'get_paginated_items_endpoint';
	const GET_PAGINATED_DEBUG_LOG_ITEMS_ENDPOINT = 'get_paginated_debug_log_items_endpoint';
	const DELETE_DEBUG_LOGS_ENDPOINT             = 'delete_debug_logs_endpoint';
	const DELETE_EMAIL_ENDPOINT                  = 'delete_email_endpoint';
	const DELETE_EVENTS_ENDPOINT                 = 'delete_events_endpoint';
	const GET_EMAIL_MESSAGE_ENDPOINT             = 'get_email_message_endpoint';
	const SCHEDULING_HANDLER                     = 'scheduling_handler';
	const LOG_ITEM_ENDPOINT                      = 'log_item_endpoint';
	const DEBUG_LOGGER                           = 'debug_logger_util';
	const VIEW_DEBUG_LOG_ENDPOINT                = 'view_debug_log_endpoint';
	const DEBUG_LOG_DIR                          = 'debug_log_dir';
	const DEBUG_LOG_FILEPATH                     = 'debug_log_filepath';
	const DEBUG_LOG_MODEL                        = 'debug_log_model';
	const DB_LOGGING_PROVIDER                    = 'db_logging_provider';
	const DEBUG_LOG_EVENT_HANDLER                = 'debug_log_event_handler';

	const LOGGING_ENDPOINTS_CONFIG = 'logging_endpoints_config';

	const RETENTION_ACTION_NAME = 'gravitysmtp_scheduler_handle_log_retention';

	const DEBUG_LOG_RETENTION_CRON_HOOK = 'gravitysmtp_scheduler_handle_debug_retention';

	protected $configs = array(
		self::LOGGING_ENDPOINTS_CONFIG => Logging_Endpoints_Config::class,
	);

	public function register( Service_Container $container ) {
		parent::register( $container );

		$this->container->add( Connector_Service_Provider::DATA_STORE_CONST, function () {
			return new Const_Data_Store();
		} );

		$this->container->add( Connector_Service_Provider::DATA_STORE_OPTS, function () {
			return new Opts_Data_Store();
		} );

		$this->container->add( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS, function () {
			return new Plugin_Opts_Data_Store();
		} );

		$this->container->add( Connector_Service_Provider::DATA_STORE_ROUTER, function () use ( $container ) {
			return new Data_Store_Router( $container->get( Connector_Service_Provider::DATA_STORE_CONST ), $container->get( Connector_Service_Provider::DATA_STORE_OPTS ), $container->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$this->container->add( self::LOGGER, function () use ( $container ) {
			return new Logger( $container->get( Connector_Service_Provider::LOG_DETAILS_MODEL ) );
		} );

		$this->container->add( self::WP_MAIL_LOGGER, function () use ( $container ) {
			return new WP_Mail_Logger( $container->get( self::LOGGER ), $container->get( Connector_Service_Provider::EVENT_MODEL ), $container->get( Utils_Service_Provider::SOURCE_PARSER ), $container->get( Utils_Service_Provider::HEADER_PARSER ) );
		} );

		$this->container->add( self::DEBUG_LOG_MODEL, function () use ( $container ) {
			return new Debug_Log_Model();
		} );

		$this->container->add( self::GET_PAGINATED_ITEMS_ENDPOINT, function () use ( $container ) {
			return new Get_Paginated_Items_Endpoint( $container->get( Connector_Service_Provider::EVENT_MODEL ), $container->get( Utils_Service_Provider::RECIPIENT_PARSER ) );
		} );

		$this->container->add( self::GET_PAGINATED_DEBUG_LOG_ITEMS_ENDPOINT, function () use ( $container ) {
			return new Get_Paginated_Debug_Log_Items_Endpoint( $container->get( self::DEBUG_LOG_MODEL ) );
		} );

		$this->container->add( self::DELETE_DEBUG_LOGS_ENDPOINT, function () use ( $container ) {
			return new Delete_Debug_Logs_Endpoint( $container->get( self::DEBUG_LOG_MODEL ) );
		} );

		$this->container->add( self::DELETE_EMAIL_ENDPOINT, function () use ( $container ) {
			return new Delete_Email_Endpoint( $container->get( Connector_Service_Provider::EVENT_MODEL ) );
		} );

		$this->container->add( self::DELETE_EVENTS_ENDPOINT, function () use ( $container ) {
			return new Delete_Events_Endpoint( $container->get( Connector_Service_Provider::EVENT_MODEL ) );
		} );

		$this->container->add( self::GET_EMAIL_MESSAGE_ENDPOINT, function () use ( $container ) {
			return new Get_Email_Message_Endpoint( $container->get( Connector_Service_Provider::EVENT_MODEL ) );
		} );

		$this->container->add( self::SCHEDULING_HANDLER, function () use ( $container ) {
			return new Handler( $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ), $container->get( Connector_Service_Provider::EVENT_MODEL ), $container->get( Connector_Service_Provider::LOG_DETAILS_MODEL ) );
		} );

		$this->container->add( self::DB_LOGGING_PROVIDER, function () use ( $container ) {
			return new DB_Logging_Provider( $container->get( self::DEBUG_LOG_MODEL ) );
		} );

		$this->container->add( self::DEBUG_LOGGER, function () use ( $container ) {
			$data      = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$log_level = $data->get_plugin_setting( Tools_Config::SETTING_DEBUG_LOG_LEVEL, DB_Logging_Provider::DEBUG );

			return new Debug_Logger( $container->get( self::DB_LOGGING_PROVIDER ), $log_level );
		} );

		$this->container->add( self::LOG_ITEM_ENDPOINT, function () use ( $container ) {
			return new Log_Item_Endpoint( $container->get( self::DEBUG_LOGGER ) );
		} );

		$this->container->add( self::VIEW_DEBUG_LOG_ENDPOINT, function () use ( $container ) {
			$debug_logger = $container->get( self::DEBUG_LOGGER );
			$debug_model  = $container->get( self::DEBUG_LOG_MODEL );

			return new View_Log_Endpoint( $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ), $debug_logger, $debug_model );
		} );

		$this->container->add( self::DEBUG_LOG_EVENT_HANDLER, function () use ( $container ) {
			return new Debug_Log_Event_Handler( $container->get( self::DEBUG_LOGGER ), $container->get( Connector_Service_Provider::DATA_STORE_ROUTER ) );
		} );

		$this->container->add( Utils_Service_Provider::ATTACHMENTS_SAVER, function () use ( $container ) {
			return new Attachments_Saver( $container->get( Logging_Service_Provider::DEBUG_LOGGER ) );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'wp_ajax_' . Get_Paginated_Items_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::GET_PAGINATED_ITEMS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Get_Paginated_Debug_Log_Items_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::GET_PAGINATED_DEBUG_LOG_ITEMS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Delete_Debug_Logs_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::DELETE_DEBUG_LOGS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Delete_Email_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::DELETE_EMAIL_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Delete_Events_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::DELETE_EVENTS_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Get_Email_Message_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::GET_EMAIL_MESSAGE_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . Log_Item_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::LOG_ITEM_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_nopriv_' . Log_Item_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::LOG_ITEM_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_' . View_Log_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::VIEW_DEBUG_LOG_ENDPOINT )->handle();
		} );

		add_action( 'wp_ajax_nopriv_' . View_Log_Endpoint::ACTION_NAME, function () use ( $container ) {
			$container->get( self::VIEW_DEBUG_LOG_ENDPOINT )->handle();
		} );

		add_action( self::RETENTION_ACTION_NAME, function () use ( $container ) {
			return $container->get( self::SCHEDULING_HANDLER )->run_log_retention();
		} );

		if ( ! Mail_Handler::is_minimally_configured() ) {
			add_filter( 'wp_mail', function ( $mail_info ) use ( $container ) {
				$container->get( self::WP_MAIL_LOGGER )->create_log( $mail_info );

				return $mail_info;
			} );

			add_action( 'wp_mail_failed', function ( $wp_error ) use ( $container ) {
				$container->get( self::WP_MAIL_LOGGER )->handle_failed( $wp_error );
			} );
		}

		if ( ! wp_next_scheduled( self::RETENTION_ACTION_NAME ) ) {
			wp_schedule_event( time(), 'daily', self::RETENTION_ACTION_NAME );
		}

		add_action( 'gravitysmtp_save_plugin_setting', function ( $setting, $value ) use ( $container ) {
			$container->get( self::DEBUG_LOG_EVENT_HANDLER )->on_setting_update( $setting, $value );
		}, 10, 2 );

		if ( ! wp_next_scheduled( self::DEBUG_LOG_RETENTION_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::DEBUG_LOG_RETENTION_CRON_HOOK );
		}

		add_action( self::DEBUG_LOG_RETENTION_CRON_HOOK, function () use ( $container ) {
			$container->get( self::DEBUG_LOG_EVENT_HANDLER )->on_retention_cron();
		} );
	}

}
