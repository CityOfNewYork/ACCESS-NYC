<?php

namespace OTGS\Installer\FP;

use OTGS\Installer\Collect\Support\Macroable;
use OTGS\Installer\FP\Traits\Functor;
use OTGS\Installer\FP\Traits\Pointed;
use OTGS\Installer\FP\Traits\ConstApplicative;
use OTGS\Installer\FP\Traits\Applicative;

/**
 * Class Either
 * @package WPML\FP
 *
 * @method static callable|Right of( ...$value ) - Curried :: a → Right a
 *
 * @method static callable|Left left( ...$value ) - Curried :: a → Left a
 *
 * @method static callable|Right right( ...$value ) - Curried :: a → Right a
 *
 * @method static callable|Left|Right fromNullable( ...$value ) - Curried :: a → Either a
 *
 * @method static callable|Left|Right fromBool( ...$value ) - Curried :: a → Either a
 *
 * @method static Either tryCatch( ...$fn ) - Curried :: a → Either a
 *
 * @method static mixed getOrElse( ...$other )
 */
abstract class Either {
	use Functor;
	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'of', Right::of() );

		self::macro( 'left', Left::of() );

		self::macro( 'right', Right::of() );

		self::macro( 'fromNullable', curryN( 1, function ( $value ) {
			return is_null( $value ) ? self::left( $value ) : self::right( $value );
		} ) );

		self::macro( 'fromBool', curryN( 1, function ( $value ) {
			return (bool) $value ? self::right( $value ) : self::left( $value );
		} ) );

	}

	/**
	 * @return Either
	 */
	public function join() {
		if ( ! $this->value instanceof Either ) {
			return $this;
		}

		return $this->value->join();
	}

	/**
	 * @param callable $fn
	 *
	 * @return mixed
	 */
	abstract public function chain( callable $fn );

	/**
	 * @param callable $leftFn
	 * @param callable $rightFn
	 *
	 * @return Either|Left|Right
	 */
	abstract public function bichain( callable $leftFn, callable $rightFn);

	/**
	 * @param callable $fn
	 *
	 * @return mixed
	 */
	abstract public function orElse( callable $fn );
	abstract public function bimap( callable $leftFn, callable $rightFn );
	abstract public function coalesce( callable $leftFn, callable $rightFn );
	abstract public function alt( Either $alt );
	abstract public function filter( callable $fn );

}

class Left extends Either {

	use ConstApplicative;
	use Pointed;

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function map( callable $fn ) {
		return $this; // noop
	}

	public function bimap( callable $leftFn, callable $rightFn ) {
		return Either::left( $leftFn( $this->value ) );
	}

	/**
	 * @param callable $leftFn
	 * @param callable $rightFn
	 *
	 * @return Right
	 */
	public function coalesce( callable $leftFn, callable $rightFn ) {
		return Either::of( $leftFn( $this->value ) );
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function get() {
		throw new \Exception( "Can't extract the value of Left" );
	}

	/**
	 * @param mixed $other
	 *
	 * @return mixed
	 */
	public function getOrElse( $other ) {
		return $other;
	}

	/**
	 * @param callable $fn
	 *
	 * @return Right
	 */
	public function orElse( callable $fn ) {
		return Either::right( $fn( $this->value ) );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function chain( callable $fn ) {
		return $this;
	}

	/**
	 * @param callable $leftFn
	 * @param callable $rightFn
	 *
	 * @return Either|Left|Right
	 */
	public function bichain( callable $leftFn, callable $rightFn ) {
		return $leftFn( $this->value );
	}

	/**
	 * @param mixed $value
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function getOrElseThrow( $value ) {
		throw new \Exception( $value );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function filter( callable $fn ) {
		return $this;
	}

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function tryCatch( callable $fn ) {
		return $this; // noop
	}

	public function alt( Either $alt ) {
		return $alt;
	}
}

class Right extends Either {

	use Applicative;
	use Pointed;

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function map( callable $fn ) {
		return Either::of( $fn( $this->value ) );
	}

	public function bimap( callable $leftFn, callable $rightFn ) {
		return $this->map( $rightFn );
	}

	/**
	 * @param callable $leftFn
	 * @param callable $rightFn
	 *
	 * @return Right
	 */
	public function coalesce( callable $leftFn, callable $rightFn ) {
		return $this->map( $rightFn );
	}

	/**
	 * @param Either $other
	 *
	 * @return mixed
	 */
	public function getOrElse( $other ) {
		return $this->value;
	}

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function orElse( callable $fn ) {
		return $this;
	}

	/**
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function getOrElseThrow( $value ) {
		return $this->value;
	}

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function chain( callable $fn ) {
		return $this->map( $fn )->join();
	}

	/**
	 * @param callable $leftFn
	 * @param callable $rightFn
	 *
	 * @return Either|Left|Right
	 */
	public function bichain( callable $leftFn, callable $rightFn ) {
		return $rightFn( $this->value );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function filter( callable $fn ) {
		return Logic::ifElse( $fn, Either::right(), Either::left(), $this->value );
	}

	/**
	 * @param callable $fn
	 *
	 * @return Either
	 */
	public function tryCatch( callable $fn ) {
		return tryCatch( function () use ( $fn ) {
			return $fn( $this->value );
		} );
	}

	public function alt( Either $alt ) {
		return $this;
	}
}

Either::init();