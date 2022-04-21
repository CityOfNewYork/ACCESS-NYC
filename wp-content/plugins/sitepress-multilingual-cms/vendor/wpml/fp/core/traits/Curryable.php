<?php


namespace WPML\FP;

use BadMethodCallException;
use Closure;

trait Curryable {
	/**
	 * The registered string curried methods.
	 *
	 * @var string[]
	 */
	protected static $curried = [];

	/**
	 * Register a custom curried function.
	 *
	 * @param string   $name
	 * @param int      $argCount
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
	 * @param string  $method
	 * @param mixed[] $parameters
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
	 * @param string  $method
	 * @param mixed[] $parameters
	 *
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	public function __call( $method, $parameters ) {
		throw new BadMethodCallException( "Curryable does not support methods in object scope. This is a limitation of PHP 5.x." );
		if ( ! static::hasCurry( $method ) ) {
			throw new BadMethodCallException( "Method {$method} does not exist." );
		}

		if ( static::$curried[ $method ][1] instanceof Closure ) {
			return call_user_func_array( $this->curryItCall( ...self::$curried[ $method ] ), $parameters );
		}

		return call_user_func_array( static::$curried[ $method ][1], $parameters );
	}

	/**
	 * @param int     $count
	 * @param \Closure $fn
	 *
	 * @return \Closure
	 */
	private function curryItCall( $count, Closure $fn ) {
		return curryN( $count, $fn->bindTo( $this, static::class ) );
	}

	/**
	 * @param int     $count
	 * @param \Closure $fn
	 *
	 * @return \Closure
	 */
	private static function curryItStaticCall( $count, Closure $fn ) {
		return curryN( $count, Closure::bind( $fn, null, static::class ) );
	}

}
