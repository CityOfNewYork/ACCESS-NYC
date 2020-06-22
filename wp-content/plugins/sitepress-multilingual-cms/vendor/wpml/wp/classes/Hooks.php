<?php

namespace WPML\LIB\WP;

use WPML\FP\Either;
use WPML\FP\Promise;

class Hooks {

	/**
	 * @param string | array $action
	 * @param int            $priority
	 * @param int            $accepted_args
	 *
	 * @return Promise
	 */
	public static function onAction( $action, $priority = 10, $accepted_args = 1 ) {
		return self::onHook( 'add_action', $action, $priority, $accepted_args );
	}

	/**
	 * @param string | array $filter
	 * @param int            $priority
	 * @param int            $accepted_args
	 *
	 * @return Promise
	 */
	public static function onFilter( $filter, $priority = 10, $accepted_args = 1 ) {
		return self::onHook( 'add_filter', $filter, $priority, $accepted_args );
	}

	/**
	 * @param callable       $fn
	 * @param string | array $actionOrFilter
	 * @param int            $priority
	 * @param int            $accepted_args
	 *
	 * @return Promise
	 */
	public static function onHook( callable $fn, $actionOrFilter, $priority = 10, $accepted_args = 1 ) {

		$actionsOrFilters = is_array( $actionOrFilter ) ? $actionOrFilter : [ $actionOrFilter ];

		$promise = new Promise();

		$callback = function () use ( $promise ) {
			return $promise->resolve( Either::right( func_get_args() ) )->getOrElse( null );
		};

		foreach ( $actionsOrFilters as $actionOrFilter ) {
			$fn( $actionOrFilter, $callback, $priority, $accepted_args );
		};

		return $promise;
	}

}
