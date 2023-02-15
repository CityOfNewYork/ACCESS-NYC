<?php

namespace WPML\FP\Functor;


trait Functor {
	/** @var mixed */
	protected $value;

	/**
	 * @param  mixed  $value
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * @return mixed
	 */
	public function get() {
		return $this->value;
	}

	/**
	 * @param callable $callback
	 *
	 * @return \WPML\FP\Either
	 */
	abstract public function map( callable $callback );

}
