<?php

namespace WPML\FP;

use Exception;
use WPML\FP\Functor\Functor;

abstract class Either {
	use Functor;

	public static function of( $value ) {
		return self::right( $value );
	}

	public static function left( $value ) {
		return new Left( $value );
	}

	public static function right( $value ) {
		return new Right( $value );
	}

	public static function fromNullable( $value ) {
		return is_null( $value ) ? self::left( $value ) : self::right( $value );
	}

	public function join() {
		if( ! $this->value instanceof Either ) {
			return $this;
		}
		return $this->value->join();
	}

	abstract public function chain( callable $fn );
	abstract public function orElse( callable $fn );
}

class Left extends Either {

	use ConstApplicative;

	public function map( callable $fn ) {
		return $this; // noop
	}

	public function get() {
		throw new Exception( "Can't extract the value of Left" );
	}

	/**
	 * @param mixed $other
	 *
	 * @return mixed
	 */
	public function getOrElse( $other ) {
		return $other;
	}

	public function orElse( callable $fn ) {
		return Either::right( $fn( $this->value ) );
	}

	public function chain( callable $fn ) {
		return $this;
	}

	public function getOrElseThrow( $value ) {
		throw new Exception( $value );
	}

	public function filter( $fn ) {
		return $this;
	}

	public function tryCatch( callable $fn ) {
		return $this; // noop
	}

}

class Right extends Either {

	use Applicative;

	public function map( callable $fn ) {
		return Either::of( $fn( $this->value ) );
	}

	public function getOrElse( $other ) {
		return $this->value;
	}

	public function orElse( callable $fn ) {
		return $this;
	}

	public function getOrElseThrow( $value ) {
		return $this->value;
	}

	public function chain( callable $fn ) {
		return $this->map($fn)->join();
	}

	public function filter( callable $fn ) {
		return Either::fromNullable( $fn( $this->value ) ? $this->value : null );
	}

	public function tryCatch( callable $fn ) {
		return tryCatch( function() use ( $fn ) { return $fn( $this->value ); } );
	}

}
