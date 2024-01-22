<?php

namespace WPML\Container;

use function WPML\FP\curryN;

if ( ! function_exists( 'WPML\Container\make' ) ) {
	/**
	 * Curried function
	 *
	 * Make returns a new instance otherwise returns a shared instance if the
	 * class_name or an instance is set as shared using the share function
	 *
	 * @param string $class_name
	 * @param array  $args
	 *
	 * @return object
	 * @throws \WPML\Auryn\InjectionException
	 *
     *
     * @phpstan-template T of object
     * @phpstan-param class-string<T> $class_name
     *
     * @phpstan-return T
	 */
	function make( $class_name = null, array $args = null ) {
		$make = function ( $class_name, $args = [] ) {
			if ( class_exists( $class_name ) || interface_exists( $class_name ) ) {
				return Container::make( $class_name, $args );
			}

			return null;
		};

		return call_user_func_array( curryN( 1, $make ), func_get_args() );
	}
}

if ( ! function_exists( 'WPML\Container\share' ) ) {

	/**
	 * class names or instances that should be shared.
	 * Shared means that only one instance is ever created when calling the make function.
	 *
	 * @param array $names_or_instances
	 *
	 * @throws \WPML\Auryn\ConfigException
	 */
	function share( array $names_or_instances ) {
		Container::share( $names_or_instances );
	}
}

if ( ! function_exists( 'WPML\Container\alias' ) ) {

	/**
	 * This allows to define aliases classes to be used in place of type hints.
	 * e.g. [
	 *          // generic => specific
	 *          'wpdb' => 'QM_DB',
	 *      ]
	 *
	 * @param array $aliases
	 *
	 * @throws \WPML\Auryn\ConfigException
	 */
	function alias( array $aliases ) {
		Container::alias( $aliases );
	}
}

if ( ! function_exists( 'WPML\Container\delegate' ) ) {

	/**
	 * This allows to delegate the object instantiation to a factory.
	 * It can be any kind of callable (class or function).
	 *
	 * @param array $delegated [ $class_name => $instantiator ]
	 *
	 * @throws \WPML\Auryn\ConfigException
	 */
	function delegate( array $delegated ) {
		Container::delegate( $delegated );
	}
}

if ( ! function_exists( 'WPML\Container\execute' ) ) {

	/**
	 * Curried function
	 *
	 * Invoke the specified callable or class::method string, provisioning dependencies along the way
	 *
	 * @param mixed $callableOrMethodStr A valid PHP callable or a provisionable ClassName::methodName string
	 * @param array $args                array specifying params with which to invoke the provisioned callable
	 *
	 * @return mixed Returns the invocation result returned from calling the generated executable
	 * @throws \WPML\Auryn\InjectionException
	 */
	function execute( $callableOrMethodStr = null, $args = null ) {
		return call_user_func_array( curryN( 1, [ Container::class, 'execute' ] ), func_get_args() );
	}
}
