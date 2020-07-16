<?php

namespace WPML\FP;

class _Invoker {

	private $fnName;
	private $args = [];

	public function __construct( $fnName ) {
		$this->fnName = $fnName;
	}

	public function with( ...$args ) {
		$this->args = $args;
		return $this;
	}

	public function __invoke( $instance ) {
		return call_user_func_array( [ $instance, $this->fnName ], $this->args );
	}
}

