<?php

namespace Gravity_Forms\Gravity_Tools\Utils;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_SMTP\Data_Store\Const_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Null_Logger;
use Gravity_Forms\Gravity_SMTP\Logging\Debug\Null_Logging_Provider;
use Gravity_Forms\Gravity_SMTP\Models\Event_Model;
use Gravity_Forms\Gravity_SMTP\Utils\AWS_Signature_Handler;
use Gravity_Forms\Gravity_SMTP\Utils\Basic_Encrypted_Hash;
use Gravity_Forms\Gravity_SMTP\Utils\Header_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Import_Data_Checker;
use Gravity_Forms\Gravity_SMTP\Utils\Recipient_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\Source_Parser;
use Gravity_Forms\Gravity_SMTP\Utils\SQL_Filter_Parser;
use Gravity_Forms\Gravity_Tools\Cache\Cache;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

class Utils_Service_Provider extends Service_Provider {

	const CACHE                 = 'cache';
	const COMMON                = 'common';
	const HEADER_PARSER         = 'header_parser';
	const IMPORT_DATA_CHECKER   = 'import_data_checker';
	const LOGGER                = 'logger_util';
	const RECIPIENT_PARSER      = 'recipient_parser';
	const SOURCE_PARSER         = 'source_parser';
	const FILTER_PARSER         = 'filter_parser';
	const ATTACHMENTS_SAVER     = 'attachments_saver';
	const AWS_SIGNATURE_HANDLER = 'aws_signature_handler';
	const BASIC_ENCRYPTED_HASH  = 'basic_encrypted_hash';

	public function register( Service_Container $container ) {
		$container->add( Connector_Service_Provider::DATA_STORE_CONST, function () {
			return new Const_Data_Store();
		} );

		$container->add( Connector_Service_Provider::DATA_STORE_OPTS, function () {
			return new Opts_Data_Store();
		} );

		$container->add( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS, function () {
			return new Plugin_Opts_Data_Store();
		} );

		$container->add( Connector_Service_Provider::DATA_STORE_ROUTER, function () use ( $container ) {
			return new Data_Store_Router( $container->get( Connector_Service_Provider::DATA_STORE_CONST ), $container->get( Connector_Service_Provider::DATA_STORE_OPTS ), $container->get( Connector_Service_Provider::DATA_STORE_PLUGIN_OPTS ) );
		} );

		$container->add( self::COMMON, function () use ( $container ) {
			$data = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$key  = $data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_LICENSE_KEY, '' );

			return new Common( GRAVITY_MANAGER_URL, GRAVITY_SUPPORT_URL, $key );
		} );

		$container->add( self::CACHE, function () use ( $container ) {
			return new Cache( $container->get( self::COMMON ) );
		} );

		$container->add( self::HEADER_PARSER, function () {
			return new Header_Parser();
		} );

		$container->add( self::IMPORT_DATA_CHECKER, function () {
			return new Import_Data_Checker();
		} );

		$container->add( self::SOURCE_PARSER, function () {
			return new Source_Parser();
		} );

		$container->add( self::LOGGER, function () {
			return new Null_Logger( new Null_Logging_Provider() );
		} );

		$container->add( self::RECIPIENT_PARSER, function () {
			return new Recipient_Parser();
		} );

		$container->add( self::FILTER_PARSER, function () {
			return new SQL_Filter_Parser();
		} );

		$container->add( self::AWS_SIGNATURE_HANDLER, function () {
			return new AWS_Signature_Handler();
		} );

		$container->add( self::BASIC_ENCRYPTED_HASH, function () {
			return new Basic_Encrypted_Hash();
		} );
	}

	public function init( \Gravity_Forms\Gravity_Tools\Service_Container $container ) {
		add_filter( 'cron_schedules', function ( $schedules ) {
			$schedules[ 'every-minute' ] = array(
				'interval' => MINUTE_IN_SECONDS,
				'display'  => esc_html__( 'Every Minute', 'gravitysmtp' ),
			);

			return $schedules;
		} );

		add_action( 'gravitysmtp_after_mail_updated', function ( $email_id, $email_data ) use ( $container ) {
			if ( ! empty( $email_data['extra'] ) && is_string( $email_data['extra'] ) ) {
				$email_data['extra'] = unserialize( $email_data['extra'] );
			}

			if ( empty( $email_data['extra']['attachments'] ) ) {
				return;
			}

			if ( ! empty( $email_data['extra']['attachments_saved'] ) ) {
				return;
			}

			/**
			 * @var Data_Store_Router $data
			 */
			$data             = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$save_attachments = $data->get_plugin_setting( Save_Plugin_Settings_Endpoint::PARAM_SAVE_ATTACHMENTS_ENABLED, 'false' );

			if ( empty( $save_attachments ) || $save_attachments === 'false' ) {
				return;
			}

			$new_attachments                          = $container->get( self::ATTACHMENTS_SAVER )->save_attachments( $email_id, $email_data['extra']['attachments'] );
			$email_data['extra']['attachments_saved'] = true;
			$email_data['extra']['attachments']       = $new_attachments;
			/**
			 * @var Event_Model $events
			 */
			$events = $container->get( Connector_Service_Provider::EVENT_MODEL );
			$events->update( array( 'extra' => serialize( $email_data['extra'] ) ), $email_id );
		}, 10, 2 );
	}
}
