<?php

namespace Gravity_Forms\Gravity_Tools\Providers;

use Gravity_Forms\Gravity_Tools\Service_Container;
use Gravity_Forms\Gravity_Tools\Service_Provider;

use Gravity_Forms\Gravity_Tools\Config_Collection;
use Gravity_Forms\Gravity_Tools\Config_Data_Parser;

/**
 * Class Config_Service_Provider
 *
 * Service provider for the Config Collection Service.
 *
 * @package Gravity_Forms\Gravity_Tools\Providers
 */
abstract class Config_Service_Provider extends Service_Provider {

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 1.0
	 *
	 * @var string[]
	 */
	protected $configs = array();

	/**
	 * Register services to the container.
	 *
	 * @since 1.0
	 *
	 * @param Service_Container $container
	 */
	public function register( Service_Container $container ) {
		// Add configs to container.
		$this->register_config_items( $container );
		$this->register_configs_to_collection( $container );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 1.0
	 *
	 * @param Service_Container $container
	 *
	 * @return void
	 */
	private function register_config_items( Service_Container $container ) {
		$parser = $container->get( Config_Collection_Service_Provider::DATA_PARSER );

		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $class, $parser ) {
				return new $class( $parser );
			} );
		}
	}

	/**
	 * Register each config defined in $configs to the GF_Config_Collection.
	 *
	 * @since 1.0
	 *
	 * @param Service_Container $container
	 *
	 * @return void
	 */
	public function register_configs_to_collection( Service_Container $container ) {
		$collection = $container->get( Config_Collection_Service_Provider::CONFIG_COLLECTION );

		foreach ( $this->configs as $name => $config ) {
			$config_class = $container->get( $name );
			$collection->add_config( $config_class );
		}
	}
}