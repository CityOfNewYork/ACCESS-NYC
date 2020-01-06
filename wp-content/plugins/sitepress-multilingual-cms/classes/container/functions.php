<?php
namespace WPML\Container;

if( ! function_exists( 'make' ) ) {
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

	function make( $class_name, array $args = array() ) {
		if ( class_exists( $class_name ) || interface_exists( $class_name ) ) {
			return Container::make( $class_name, $args );
		}

		return null;
	}
}

if( ! function_exists( 'share' ) ) {

	/**
	 * class names or instances that should be shared.
	 * Shared means that only one instance is ever created when calling the make function.
	 *
	 * @param array $names_or_instances
	 */

	function share( array $names_or_instances ) {
		Container::share( $names_or_instances );
	}
}

if( ! function_exists( 'alias' ) ) {

	/**
	 * This allows to define aliases classes to be used in place of type hints.
	 * e.g. [
	 *          // generic => specific
	 *          'wpdb' => 'QM_DB',
	 *      ]
	 *
	 * @param array $aliases
	 */
	function alias( array $aliases ) {
		Container::alias( $aliases );
	}
}

if( ! function_exists( 'delegate' ) ) {

	/**
	 * This allows to delegate the object instantiation to a factory.
	 * It can be any kind of callable (class or function).
	 *
	 * @param array $delegated [ $class_name => $instantiator ]
	 */
	function delegate( array $delegated ) {
		Container::delegate( $delegated );
	}
}
