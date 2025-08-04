<?php

namespace Gravity_Forms\Gravity_Tools\Providers;

use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

use Gravity_Forms\Gravity_Tools\Config_Collection;
use Gravity_Forms\Gravity_Tools\Config_Data_Parser;

/**
 * Class Config_Collection_Service_Provider
 *
 * Service provider for the Config Collection Service.
 *
 * @package Gravity_Forms\Gravity_Tools\Providers
 */
class Config_Collection_Service_Provider extends Service_Provider {

	// Organizational services
	const CONFIG_COLLECTION = 'config_collection';
	const DATA_PARSER       = 'data_parser';

	protected $rest_namespace;

	/**
	 * The $rest_namespace will be used in order to define the API endpoint for
	 * retrieving mock/config data.
	 *
	 * @since 1.0
	 *
	 * @param string $rest_namespace
	 *
	 * @return void
	 */
	public function __construct( $rest_namespace = 'gravityforms/v2' ) {
		$this->rest_namespace = $rest_namespace;
	}

	/**
	 * Register services to the container.
	 *
	 * @since 1.0
	 *
	 * @param Service_Container $container
	 */
	public function register( Service_Container $container ) {

		// Add to container
		$container->add( self::CONFIG_COLLECTION, function () {
			return new Config_Collection();
		} );

		$container->add( self::DATA_PARSER, function () {
			return new Config_Data_Parser();
		} );
	}

	/**
	 * Initiailize any actions or hooks.
	 *
	 * @since 1.0
	 *
	 * @param Service_Container $container
	 *
	 * @return void
	 */
	public function init( Service_Container $container ) {

		// Need to pass $this to callbacks; save as variable.
		$self = $this;

		add_action( 'wp_enqueue_scripts', function () use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle();
		}, 9999 );

		add_action( 'admin_enqueue_scripts', function () use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle();
		}, 9999 );

		add_action( 'gform_preview_init', function () use ( $container ) {
			$container->get( self::CONFIG_COLLECTION )->handle();
		}, 0 );

		add_action( 'rest_api_init', function () use ( $container, $self ) {
			register_rest_route( $this->rest_namespace, '/tests/mock-data', array(
				'methods'             => 'GET',
				'callback'            => array( $self, 'config_mocks_endpoint' ),
				'permission_callback' => function () {
					return true;
				},
			) );
		} );
	}

	/**
	 * Callback for the Config Mocks REST endpoint.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function config_mocks_endpoint() {
		define( 'GFORMS_DOING_MOCK', true );
		$data      = $this->container->get( self::CONFIG_COLLECTION )->handle( false );

		return $data;
	}
}