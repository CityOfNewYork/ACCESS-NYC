<?php

namespace Gravity_Forms\Gravity_SMTP\Tracking;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Tracking_Service_Provider extends Service_Provider {

	const OPEN_PIXEL_HANDLER = 'open_pixel_handler';

	const SETTING_OPEN_TRACKING = 'open_tracking';

	public function register( Service_Container $container ) {
		$container->add( self::OPEN_PIXEL_HANDLER, function() use ( $container ) {
			$encrypter = $container->get( Utils_Service_Provider::BASIC_ENCRYPTED_HASH );
			$events = $container->get( Connector_Service_Provider::EVENT_MODEL );
			return new Open_Pixel_Handler( $encrypter, $events );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'gravitysmtp_after_connector_init', function ( $email_id, Connector_Base $connector ) use ( $container ) {
			$data    = $container->get( Connector_Service_Provider::DATA_STORE_ROUTER );
			$enabled = $data->get_plugin_setting( self::SETTING_OPEN_TRACKING, false );

			if ( ! Booliesh::get( $enabled ) ) {
				return;
			}

			$atts    = $connector->get_atts();
			$is_html = ! empty( $atts['headers']['content-type'] ) && strpos( $atts['headers']['content-type'], 'text/html' ) !== false;

			if ( ! $is_html ) {
				return;
			}

			$message  = isset( $atts['message'] ) ? $atts['message'] : '';
			$modified = $container->get( self::OPEN_PIXEL_HANDLER )->add_pixel( $email_id, $message, $atts );

			if ( $modified === $message ) {
				return;
			}

			$connector->set_att( 'message', $modified );
		}, 10, 2 );

		add_action( 'template_redirect', function() use ( $container ) {
			if ( get_query_var( Open_Pixel_Handler::REWRITE_PARAM ) == false || get_query_var( Open_Pixel_Handler::REWRITE_PARAM ) == '' ) {
				return;
			}

			$container->get( self::OPEN_PIXEL_HANDLER )->handle_redirect();
		} );

		$container->get( self::OPEN_PIXEL_HANDLER )->add_rewrite_rules();
	}

}