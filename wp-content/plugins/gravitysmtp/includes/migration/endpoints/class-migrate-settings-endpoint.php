<?php

namespace Gravity_Forms\Gravity_SMTP\Apps\Migration\Endpoints;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Factory;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Migration\Migrator_Collection;
use Gravity_Forms\Gravity_Tools\Endpoints\Endpoint;
use Gravity_Forms\Gravity_Tools\License\License_Statuses;
use Gravity_Forms\Gravity_Tools\Updates\Updates_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Migration\Data;

class Migrate_Settings_Endpoint extends Endpoint {

	const PARAM_PLUGIN_TO_MIGRATE = 'plugin_to_migrate';
	const ACTION_NAME             = 'migrate_settings';

	/**
	 * @var Connector_Factory $connector_factory
	 */
	protected $connector_factory;

	/**
	 * @var Opts_Data_Store
	 */
	protected $data;

	/**
	 * @var string
	 */
	protected $base_namespace;

	/**
	 * @var array
	 */
	protected $connectors;

	protected $required_params = array(
		self::PARAM_PLUGIN_TO_MIGRATE,
	);

	public function __construct( $connector_factory, $data_store, $base_namespace, $connectors ) {
		$this->connector_factory = $connector_factory;
		$this->data              = $data_store;
		$this->base_namespace    = $base_namespace;
		$this->connectors        = $connectors;
	}

	protected function get_nonce_name() {
		return self::ACTION_NAME;
	}

	public function handle() {
		if ( ! $this->validate() ) {
			wp_send_json_error( 'Missing required parameters.', 400 );
		}

		$plugin_to_migrate = filter_input( INPUT_POST, self::PARAM_PLUGIN_TO_MIGRATE, FILTER_DEFAULT );
		$handler_classname = $this->base_namespace . '\\Data\\Migration_Data_' . ucfirst( strtolower( $plugin_to_migrate ) );

		if ( ! class_exists( $handler_classname ) ) {
			/* translators: %1$s: plugin name */
			wp_send_json_error( sprintf( __( 'Could not find handler for migration type: %1$s', 'gravitysmtp' ), $plugin_to_migrate ), 400 );
		}

		$handler = new $handler_classname();

		/**
		 * @var Migrator_Collection $migrations
		 */
		$migrations = $handler->get_migrations();
		$migrations->run( $plugin_to_migrate );

		$response = array();

		foreach ( $this->connectors as $connector_name => $connector_class ) {
			$instance                    = $this->connector_factory->create( $connector_name );
			$connector_data              = $instance->get_data();
			$response[ strtolower( $connector_name ) ] = $connector_data['data'];
		}

		wp_send_json_success( $response );
	}

}
