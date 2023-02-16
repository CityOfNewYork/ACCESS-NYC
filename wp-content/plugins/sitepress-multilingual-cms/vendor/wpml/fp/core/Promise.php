<?php

namespace WPML\FP;

class Promise {

	/** @var callable */
	private $onResolved;

	/** @var callable */
	private $onReject;

	/** @var Promise */
	private $next;

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function resolve( $data ) {
		if ( $this->onResolved ) {
			if ( $data instanceof Either ) {
				$result = $data->chain( $this->onResolved );
			} else {
				$result = call_user_func( $this->onResolved, $data );
			}
			if ( $this->next ) {
				if ( $result && ! $result instanceof Left ) {
					return $this->next->resolve( $result );
				} else {
					return $this->next->reject( $result );
				}
			} else {
				return $result;
			}
		} else {
			return $data;
		}
	}

	/**
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	public function reject( $data ) {
		$result = $data;
		if ( $this->onReject ) {
			if ( $data instanceof Either ) {
				$result = $data->orElse( $this->onReject )->join();
			} else {
				$result = call_user_func( $this->onReject, $data );
			}
		}
		if ( $this->next ) {
			return $this->next->reject( $result );
		} else {
			return $result;
		}
	}

	/**
	 * @param callable $fn
	 *
	 * @return Promise
	 */
	public function then( callable $fn ) {
		$this->onResolved = $fn;
		$this->next       = new Promise();

		return $this->next;
	}

	/**
	 * @param callable $fn
	 *
	 * @return Promise
	 */
	public function onError( callable $fn ) {
		$this->onReject = $fn;
		$this->next     = new Promise();

		return $this->next;
	}

}
