<?php

namespace WPML\FP;

use Exception;
use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Functor\Functor;

/**
 * Class Maybe
 * @package WPML\FP
 * @method static callable|Just|Nothing fromNullable( ...$value ) - Curried :: a → Nothing | Just a
 */
class Maybe {

	use Macroable;

	/**
	 * @param $value
	 *
	 * @return Just
	 */
	public static function just( $value ) {
		return new Just( $value );
	}

	/**
	 * @return Nothing
	 */
	public static function nothing() {
		return new Nothing();
	}

	/**
	 * @param $value
	 *
	 * @return Just
	 */
	public static function of( $value ) {
		return self::just( $value );
	}

	/**
	 * @return bool
	 */
	public function isNothing() {
		return false;
	}

	/**
	 * @return bool
	 */
	public function isJust() {
		return false;
	}
}

/**
 * @param mixed $value
 *
 * @return Just|Nothing
 */
Maybe::macro( 'fromNullable', curryN( 1, function( $value ) {
	return is_null( $value ) || $value === false ? Maybe::nothing() : Maybe::just( $value );
} ) );


class Just extends Maybe {
	use Functor;
	use Applicative;

	/**
	 * @param callable $fn
	 *
	 * @return Just|Nothing
	 */
	public function map( callable $fn ) {
		return Maybe::fromNullable( $fn( $this->value ) );
	}

	/**
	 * @param mixed $other
	 *
	 * @return mixed
	 */
	public function getOrElse( $other ) {
		return $this->value;
	}

	/**
	 * @param callable $fn
	 *
	 * @return Just|Nothing
	 */
	public function filter( $fn = null ) {
		$fn = $fn ?: FP::identity();
		return Maybe::fromNullable( $fn( $this->value ) ? $this->value : null );
	}

	/**
	 * @param callable $fn
	 *
	 * @return mixed
	 */
	public function chain( callable $fn ) {
		return $fn( $this->value );
	}

	/**
	 * @return bool
	 */
	public function isJust() {
		return true;
	}
}

class Nothing extends Maybe {
	use ConstApplicative;

	/**
	 * @param callable
	 *
	 * @return Nothing
	 */
	public function map( callable $fn ) {
		return $this;
	}

	public function get() {
		throw new Exception( "Can't extract the value of Nothing" );
	}

	/**
	 * @param mixed|callable $other
	 *
	 * @return mixed
	 */
	public function getOrElse( $other ) {
		return value( $other );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Nothing
	 */
	public function filter( callable $fn ) {
		return $this;
	}

	/**
	 * @param callable $fn
	 *
	 * @return Nothing
	 */
	public function chain( callable $fn ) {
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isNothing() {
		return true;
	}

}
