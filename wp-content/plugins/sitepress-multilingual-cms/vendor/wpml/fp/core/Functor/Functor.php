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

	abstract public function map( callable $callback );

	public static function of( $value ) {
		return new static( $value );
	}
}
