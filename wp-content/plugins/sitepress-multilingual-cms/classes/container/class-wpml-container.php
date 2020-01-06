<?php
namespace WPML\Container;

use Auryn\Injector as AurynInjector;

class Container {

	/** @var Container $instance */
	private static $instance = null;

	/** @var AurynInjector|null  */
	private $injector = null;

	private function __construct() {
		$this->injector = new AurynInjector();
	}

	/**
	 * @return Container
	 */
	static public function get_instance() {
		if( ! self::$instance ) {
			self::$instance = new Container();
		}

		return self::$instance;
	}

	/**
	 * class names or instances that should be shared.
	 * Shared means that only one instance is ever created when calling the make function.
	 *
	 * @param array $names_or_instances
	 */
	static public function share( array $names_or_instances ) {
		$injector = self::get_instance()->injector;

		wpml_collect( $names_or_instances )->each( function ( $name_or_instance ) use ( $injector ) {
			$injector->share( $name_or_instance );
		});
	}

	/**
	 * This allows to define aliases classes to be used in place of type hints.
	 * e.g. [
	 *          // generic => specific
	 *          'wpdb' => 'QM_DB',
	 *      ]
	 *
	 * @param array $aliases
	 */
	static public function alias( array $aliases ) {
		$injector = self::get_instance()->injector;

		wpml_collect( $aliases )->each( function ( $alias, $original ) use ( $injector ) {
			$injector->alias( $original, $alias );
		});
	}

	/**
	 * This allows to delegate the object instantiation to a factory.
	 * It can be any kind of callable (class or function).
	 *
	 * @param array $delegated [ $class_name => $instantiator ]
	 */
	static public function delegate( array $delegated ) {
		$injector = self::get_instance()->injector;

		wpml_collect( $delegated )->each( function ( $instantiator, $class_name ) use ( $injector ) {
			$injector->delegate( $class_name, $instantiator );
		});
	}

	/**
	 * Make returns a new instance otherwise returns a shared instance if the
	 * class_name or an instance is set as shared using the share function
	 *
	 * @param string $class_name
	 * @param array $args
	 *
	 * @return mixed
	 * @throws \Auryn\InjectionException
	 */
	static public function make( $class_name, array $args = array() ) {
		return self::get_instance()->injector->make( $class_name, $args );
	}

}
