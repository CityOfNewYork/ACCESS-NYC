<?php

namespace WPML\FP\System;

use WPML\Collect\Support\Collection;

class _Filter {

	/**
	 * @var string
	 */
	private $key;
	/**
	 * @var callable
	 */
	private $fn;
	/**
	 * @var mixed
	 */
	private $default;

	/**
	 * _Filter constructor.
	 *
	 * @param string $key
	 */
	public function __construct( $key ) {
		$this->key = $key;
	}

	/**
	 * @param callable $fn
	 *
	 * @return _Filter
	 */
	public function using( callable $fn ) {
		$this->fn = $fn;

		return $this;
	}

	/**
	 * @param mixed $default
	 *
	 * @return _Filter
	 */
	public function defaultTo( $default ) {
		$this->default = $default;

		return $this;
	}

	/**
	 * @param \WPML\Collect\Support\Collection<mixed> $collection
	 *
	 * @return \WPML\Collect\Support\Collection<mixed>
	 */
	public function __invoke( Collection $collection ) {
		$value = $collection->has( $this->key )
			? call_user_func( $this->fn, $collection->get( $this->key ) )
			: value( $this->default );

		return $collection->put( $this->key, $value );
	}
}
