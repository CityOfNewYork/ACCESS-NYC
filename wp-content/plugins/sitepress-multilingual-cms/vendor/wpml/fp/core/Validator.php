<?php

namespace WPML\FP\System;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;

class _Validator {

	private $key;
	private $fn;
	private $error;

	public function __construct( $key ) {
		$this->key = $key;
	}

	public function using( callable $fn ) {
		$this->fn = $fn;

		return $this;
	}

	public function error( $error ) {
		$this->error = $error;

		return $this;
	}

	public function __invoke( Collection $collection ) {
		return call_user_func( $this->fn, $collection->get( $this->key ) )
			? Either::right( $collection )
			: Either::left( value( $this->error ) );
	}
}

