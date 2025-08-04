<?php

namespace Gravity_Forms\Gravity_Tools\Updates;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Connectors\Endpoints\Save_Plugin_Settings_Endpoint;
use Gravity_Forms\Gravity_Tools\API\Gravity_Api;
use Gravity_Forms\Gravity_Tools\Cache\Cache;
use Gravity_Forms\Gravity_Tools\Data\Transient_Strategy;
use Gravity_Forms\Gravity_Tools\License\License_API_Connector;
use Gravity_Forms\Gravity_Tools\License\License_API_Response_Factory;
use Gravity_Forms\Gravity_Tools\Model\Form_Model;
use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;
use Gravity_Forms\Gravity_Tools\Utils\Utils_Service_Provider;

class Updates_Service_Provider extends Service_Provider {

	const AUTO_UPDATE_HANDLER          = 'update_handler';
	const LICENSE_API_CONNECTOR        = 'license_api_connector';
	const TRANSIENT_STRATEGY           = 'transient_strategy';
	const LICENSE_API_RESPONSE_FACTORY = 'license_api_response_factory';
	const GRAVITY_API                  = 'gravity_api';
	const FORM_MODEL                   = 'form_model';

	protected $full_path;

	public function __construct( $full_path ) {
		$this->full_path = $full_path;
	}

	public function register( Service_Container $container ) {
		$container->add( self::TRANSIENT_STRATEGY, function () {
			return new Transient_Strategy();
		} );

		$container->add( self::FORM_MODEL, function () use ( $container ) {
			return new Form_Model( $container->get( Utils_Service_Provider::COMMON ) );
		} );

		$container->add( self::GRAVITY_API, function () use ( $container ) {
			return new Gravity_Api( $container->get( Utils_Service_Provider::COMMON ), $container->get( self::FORM_MODEL ), 'gsmtp' );
		} );

		$container->add( self::LICENSE_API_RESPONSE_FACTORY, function () use ( $container ) {
			return new License_API_Response_Factory( $container->get( self::TRANSIENT_STRATEGY ), $container->get( Utils_Service_Provider::COMMON ) );
		} );

		$container->add( self::LICENSE_API_CONNECTOR, function () use ( $container ) {
			return new License_API_Connector( $container->get( self::GRAVITY_API ), $container->get( Utils_Service_Provider::CACHE ), $container->get( self::LICENSE_API_RESPONSE_FACTORY ), $container->get( Utils_Service_Provider::COMMON ), 'gsmtp' );
		} );

		$container->add( self::AUTO_UPDATE_HANDLER, function () use ( $container ) {
			$common            = $container->get( Utils_Service_Provider::COMMON );
			$license_connector = $container->get( self::LICENSE_API_CONNECTOR );

			return new Auto_Updater( 'gravitysmtp', GF_GRAVITY_SMTP_VERSION, 'Gravity SMTP', $this->full_path, 'gravitysmtp/gravitysmtp.php', 'https://gravityforms.com', 'https://cdn.gravity.com/gravitysmtp/icon-256x256.gif', $common, $license_connector, false );
		} );
	}

	public function init( Service_Container $container ) {
		add_action( 'init', function () use ( $container ) {
			$container->get( self::AUTO_UPDATE_HANDLER )->init();
		}, 999 );

		add_filter( 'gravity_api_check_license_params', function( $params ) {
			$params['product_code'] = 'GSMTP';

			return $params;
		});

		add_filter( 'gravity_api_remote_post_params', function ( $params ) use ( $container ) {
			$params['plugins'] = json_encode( $params['plugins'] );
			$params['version'] = 2;

			return $params;
		}, 9999 );

		/**
		 * Ensure that our transients are always flushed when visiting the Updates or
		 * Plugins pages. This ensures that updates are always shown as soon as they are available
		 * if those pages are visited.
		 *
		 * @since 1.2.0
		 */
		add_action( 'init', function () use ( $container ) {
			global $pagenow;

			if ( $pagenow !== 'update-core.php' && $pagenow !== 'plugins.php' ) {
				return;
			}

			$transient_key = 'GFCache_' . wp_hash( 'gsmtp_gforms_plugins' );

			add_filter( 'transient_' . $transient_key, function() {
				return false;
			} );

			add_filter( 'site_transient_' . $transient_key, function() {
				return false;
			} );
		}, 999 );
	}

}
