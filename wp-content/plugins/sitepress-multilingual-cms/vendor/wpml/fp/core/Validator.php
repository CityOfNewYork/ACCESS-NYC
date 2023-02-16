<?php

namespace WPML\FP\System;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;

class _Validator {

	/**
	 * @var string
	 */
	private $key;
	/**
	 * @var callable
	 */
	private $fn;
	/**
	 * @var string
	 */
	private $error;

	/**
	 * _Validator constructor.
	 *
	 * @param string $key
	 */
	public function __construct( $key ) {
		$this->key = $key;
	}

	/**
	 * @param callable $fn
	 *
	 * @return _Validator
	 */
	public function using( callable $fn ) {
		$this->fn = $fn;

		return $this;
	}

	/**
	 * @param string $error
	 *
	 * @return _Validator
	 */
	public function error( $error ) {
		$this->error = $error;

		return $this;
	}

	/**
	 * @param \WPML\Collect\Support\Collection<mixed> $collection
	 *
	 * @return callable|\WPML\FP\Either
	 */
	public function __invoke( Collection $collection ) {
		return call_user_func( $this->fn, $collection->get( $this->key ) )
			? Either::right( $collection )
			: Either::left( value( $this->error ) );
	}
}

