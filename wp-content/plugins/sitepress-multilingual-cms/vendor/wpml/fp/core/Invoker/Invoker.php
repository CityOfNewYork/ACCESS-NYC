<?php

namespace WPML\FP\Invoker;

class _Invoker {

	/**
	 * @var string
	 */
	private $fnName;
	/**
	 * @var mixed[]
	 */
	private $args = [];

	/**
	 * _Invoker constructor.
	 *
	 * @param string $fnName
	 */
	public function __construct( $fnName ) {
		$this->fnName = $fnName;
	}

	/**
	 * @param mixed ...$args
	 *
	 * @return _Invoker
	 */
	public function with( ...$args ) {
		$this->args = $args;
		return $this;
	}

	/**
	 * @param mixed $instance
	 *
	 * @return mixed
	 */
	public function __invoke( $instance ) {
		return call_user_func_array( [ $instance, $this->fnName ], $this->args );
	}
}

