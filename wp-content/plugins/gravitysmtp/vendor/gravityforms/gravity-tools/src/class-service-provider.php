<?php

namespace Gravity_Forms\Gravity_Tools;

use Gravity_Forms\Gravity_Tools\Service_Container;

/**
 * Class Service_Provider
 *
 * An abstraction which provides a contract for defining Service Providers. Service Providers facilitate
 * organizing Services into discreet modules, as opposed to having to register each service in a single location.
 *
 * @package Gravity_Forms\Gravity_Tools
 */
abstract class Service_Provider {

	/**
	 * @var Service_Container $container
	 */
	protected $container;

	public function set_container( Service_Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register new services to the Service Container.
	 *
	 * @param Service_Container $container
	 *
	 * @return void
	 */
	abstract public function register( Service_Container $container );

	/**
	 * Noop by default - used to initialize hooks and filters for the given module.
	 */
	public function init( Service_Container $container ) {}

}
