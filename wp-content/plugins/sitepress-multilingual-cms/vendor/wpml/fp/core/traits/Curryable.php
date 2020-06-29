<?php


namespace WPML\FP;

use Closure;
use BadMethodCallException;

trait Curryable {
	/**
	 * The registered string curried methods.
	 *
	 * @var array
	 */
	protected static $curried = [];

	/**
	 * Register a custom curried function.
	 *
	 * @param string   $name
	 * @param callable $fn
	 *
	 * @return void
	 */
	public static function curryN( $name, $argCount, callable $fn ) {
		static::$curried[ $name ] = [ $argCount, $fn ];
	}

	/**
	 * Checks if curried function is registered.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function hasCurry( $name ) {
		return isset( static::$curried[ $name ] );
	}

	/**
	 * Dynamically handle calls to the class.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public static function __callStatic( $method, $parameters ) {
		if ( ! static::hasCurry( $method ) ) {
			throw new BadMethodCallException( "Method {$method} does not exist." );
		}

		if ( static::$curried[ $method ][1] instanceof Closure ) {
			return call_user_func_array( self::curryItStaticCall( ...static::$curried[ $method ] ), $parameters );
		}

		return call_user_func_array( static::$curried[ $method ][1], $parameters );
	}

	/**
	 * Dynamically handle calls to the class.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call( $method, $parameters ) {
		if ( ! static::hasCurry( $method ) ) {
			throw new BadMethodCallException( "Method {$method} does not exist." );
		}

		if ( static::$curried[ $method ][1] instanceof Closure ) {
			return call_user_func_array( $this->curryItCall( ...self::$curried[ $method ] ), $parameters );
		}

		return call_user_func_array( static::$curried[ $method ][1], $parameters );
	}

	private function curryItCall( $count, callable $fn ) {
		return curryN( $count, $fn->bindTo( $this, static::class ) );
	}

	private static function curryItStaticCall( $count, callable $fn ) {
		return curryN( $count, Closure::bind( $fn, null, static::class ) );
	}

}
