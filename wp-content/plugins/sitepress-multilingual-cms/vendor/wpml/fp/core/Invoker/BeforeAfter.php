<?php

namespace WPML\FP\Invoker;

use function WPML\FP\pipe;

/**
 * Class BeforeAfterInvoker
 * @package WPML\FP
 *
 * Helper class that allows a before function and an after function to be run
 * when running another function
 *
 * ```php
 * $withFilter = function ( $name, $filterFn, $priority = 10, $count = 1 ) {
 *     $before = partial( 'add_filter', $name, $filterFn, $priority, $count );
 *     $after = partial( 'remove_filter', $name, $filterFn, $priority, $count );
 *     return BeforeAfterInvoker::of( $before, $after );
 * }
 *
 * $result = $withFilter( 'query', $some_callback, 10, 1 )
 *          ->invoke( $somefunction )
 *          ->runWith( 123, 456 )
 * ```
 *
 * This will call add_filter to add the filter then call the function to be invoked
 * and finally call remove_filter to remove the filter.
 *
 */
class BeforeAfter {

	/** @var callable $fn */
	private $fn;

	/** @var callable $before */
	private $before;

	/** @var callable $after */
	private $after;

	private function __construct( callable $before, callable $after ) {
		$this->before = $before;
		$this->after = $after;
	}

	/**
	 * Set the function to be invoked
	 * @param callable $fn
	 *
	 * @return $this
	 */
	public function invoke( callable $fn ) {
		$this->fn = $fn;
		return $this;
	}

	/**
	 * Add another pair of before and after functions.
	 * When it's finally run it executes before1 then before2
	 * then the function
	 * then after2 followed by after1
	 *
	 * @param callable $before
	 * @param callable $after
	 *
	 * @return BeforeAfter
	 */
	public function then( callable $before, callable $after ) {
		return new BeforeAfter(
			pipe( $this->before, $before ),
			pipe( $after, $this->after ) // Note: run after functions in reverse order
		);
	}

	/**
	 * Invoke the function with the arguments
	 * Calls the before function first then the function and then the after function
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	public function runWith( ...$args ) {
		call_user_func( $this->before );
		$result = call_user_func_array( $this->fn, $args );
		call_user_func( $this->after );
		return $result;
	}

	public static function of( callable $before, callable $after ) {
		return new BeforeAfter( $before, $after );
	}
}

