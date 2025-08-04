<?php

namespace Gravity_Forms\Gravity_Tools;

use Gravity_Forms\Gravity_Tools\Service_Provider;

/**
 * Class Service_Container
 *
 * A simple Service Container used to collect and organize Services used by the application and its modules.
 *
 * @package Gravity_Forms\Gravity_Tools
 */
class Service_Container {

	private $services = array();
	private $providers = array();

	/**
	 * Add a service to the container.
	 *
	 * @since 1.0
	 *
	 * @param string $name   The service Name
	 * @param mixed $service The service to add
	 */
	public function add( $name, $service, $defer = false ) {
		if ( empty( $name ) ) {
			$name = get_class( $service );
		}

		if ( ! $defer && is_callable( $service ) ) {
			$service = $service();
		}

		$this->services[ $name ] = $service;
	}

	/**
	 * Remove a service from the container.
	 *
	 * @since 1.0
	 *
	 * @param string $name The service name.
	 */
	public function remove( $name ) {
		unset( $this->services[ $name ] );
	}

	/**
	 * Get a service from the container by name.
	 *
	 * @since 1.0
	 *
	 * @param string $name The service name.
	 *
	 * @return mixed|null
	 */
	public function get( $name ) {
		if ( ! isset( $this->services[ $name ] ) ) {
			return null;
		}

		if ( is_callable( $this->services[ $name ] ) ) {
			$called                  = $this->services[ $name ]();
			$this->services[ $name ] = $called;
		}

		return $this->services[ $name ];
	}

	/**
	 * Add a service provider to the container and register each of its services.
	 *
	 * @since 1.0
	 *
	 * @param Service_Provider $provider
	 */
	public function add_provider( Service_Provider $provider ) {
		$provider_name = get_class( $provider );

		// Only add providers a single time.
		if ( isset( $this->providers[ $provider_name ] ) ) {
			return;
		}

		$this->providers[ $provider_name ] = $provider;

		$provider->set_container( $this );
		$provider->register( $this );
		$provider->init( $this );
	}

}
