<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;
use WPML\FP\Functor\Functor;
use WPML\FP\Functor\Pointed;

/**
 * Class Maybe
 * @package WPML\FP
 * @method static callable|Just|Nothing fromNullable( ...$value ) - Curried :: a → Nothing | Just a
 *
 * if $value is null or false it returns a Nothing otherwise returns a Just containing the value
 *
 * @method static callable safe( ...$fn ) - Curried :: ( a → b ) → ( a → Maybe b )
 *
 * returns a function that when called will run the passed in function and put the result into a Maybe
 *
 * @method static callable safeAfter( ...$predicate, ...$fn ) - Curried :: ( b → bool ) → ( a → b ) → ( a → Maybe b )
 *
 * returns a function that when called will run the passed in function and pass the result of the function
 * to the predicate. If the predicate returns true the result will be a Just containing the result of the function.
 * Otherwise it returns a Nothing if the predicate returns false.
 *
 * @method static callable safeBefore( ...$predicate, ...$fn ) - Curried :: ( a → bool ) → ( a → b ) → ( a → Maybe b )
 *
 * returns a function that when called will pass the given value to the predicate.
 * If the predicate returns true the value will be lifted into a Just instance and
 * the passed in function will then be mapped.
 * Otherwise it returns a Nothing if the predicate returns false.
 *
 * @method static callable|Just just( ...$value ) - Curried :: a → Just a
 *
 * returns a Just containing the value.
 *
 * @method static callable|Just of( ...$value ) - Curried :: a → Just a
 *
 * returns a Just containing the value.
 *
 */
class Maybe {

	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'just', Just::of() );

		self::macro( 'of', Just::of() );

		self::macro( 'fromNullable', curryN( 1, function( $value ) {
			return is_null( $value ) || $value === false ? self::nothing() : self::just( $value );
		} ) );

		Maybe::macro( 'safe', curryN( 1, function( $fn ) {
			return pipe( $fn, self::fromNullable() );
		} ) );

		Maybe::macro( 'safeAfter', curryN( 2, function( $predicate, $fn ) {
			return pipe( $fn, Logic::ifElse( $predicate, self::just(), [ self::class, 'nothing' ] ) );
		} ) );

		Maybe::macro( 'safeBefore', curryN( 2, function( $predicate, $fn ) {
			return pipe( Logic::ifElse( $predicate, self::just(), [ self::class, 'nothing' ] ), Fns::map( $fn ) );
		} ) );

	}

	/**
	 * @return Nothing
	 */
	public static function nothing() {
		return new Nothing();
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


class Just extends Maybe {
	use Functor;
	use Pointed;
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
		$fn = $fn ?: Fns::identity();
		return Maybe::fromNullable( $fn( $this->value ) ? $this->value : null );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Just|Nothing
	 */
	public function reject( $fn = null ) {
		$fn = $fn ?: Fns::identity();
		return $this->filter( Logic::complement( $fn ) );
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
	 * @param callable $fn
	 *
	 * @return Nothing
	 */
	public function map( callable $fn ) {
		return $this;
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function get() {
		throw new \Exception( "Can't extract the value of Nothing" );
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
	public function reject( callable $fn ) {
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

Maybe::init();
