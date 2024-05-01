<?php

namespace OTGS\Installer\FP;

/**
 * Returns new function which will behave like $function with
 * predefined left arguments passed to partial
 *
 * @param callable $function
 * @param mixed ...$args
 *
 * @return callable
 */
function partial( callable $function, $args ) {
	$args = array_slice( func_get_args(), 1 );

	return function () use ( $function, $args ) {
		return call_user_func_array( $function, array_merge( $args, func_get_args() ) );
	};
}

/**
 * Returns new function which applies each given function to the result of another from left to right
 * pipe(f, g, h)(x) is the same as h(g(f(x)))
 *
 * @param callable $f
 * @param callable $g
 *
 * @return callable
 */
function pipe( callable $f, callable $g ) {
	return call_user_func_array( 'OTGS\Installer\FP\compose', array_reverse( func_get_args() ) );
}

/**
 * Returns new function which applies each given function to the result of another from right to left
 * compose(f, g, h)(x) is the same as f(g(h(x)))
 *
 * @param callable $f
 * @param callable $g
 *
 * @return callable
 */
function compose( callable $f, callable $g ) {
	$functions = func_get_args();

	return function () use ( $functions ) {
		$args = func_get_args();
		foreach ( array_reverse( $functions ) as $function ) {
			$args = array( call_user_func_array( $function, $args ) );
		}

		return current( $args );
	};
}


/**
 * @param callable $fn
 *
 * @return \Closure
 */
function flip( callable $fn ) {
	return function () use ( $fn ) {
		$args = func_get_args();
		if ( count( $args ) > 1 ) {
			$temp    = $args[0];
			$args[0] = $args[1];
			$args[1] = $temp;
		}

		return call_user_func_array( $fn, $args );
	};
}

/**
 * Wraps the given function and returns a function that can take individual arguments and invokes
 * the wrapped function with individual arguments gathered into an array
 *
 * @param callable $fn
 *
 * @return \Closure
 */
function gatherArgs( callable $fn ) {
	return function ( ...$args ) use ( $fn ) {
		return $fn( $args );
	};
}

/**
 * Returns an Invoker that runs the member function. Use `with` to set the arguments
 * of the member function and then invoke with `()`
 *
 * eg. give Test class:
 * class Test {
 *
 *    private $times;
 *
 *    public function __construct( $times ) {
 *       $this->times = $times;
 *    }
 *
 *    public function multiply( $x ) {
 *       return $x * $this->times;
 *    }
 * }
 *
 * $invoker = invoke( 'multiply' )->with( 10 );
 * $result = $invoker( new Test( 2 ) );  // 20
 *
 *
 * @param string $fnName
 *
 * @return _Invoker
 */
function invoke( $fnName ) {
	return new _Invoker( $fnName );
}

/**
 * @param int      $count
 * @param callable $fn
 *
 * @return \Closure
 */
function curryN( $count, Callable $fn ) {
	$accumulator = function ( array $arguments ) use ( $count, $fn, &$accumulator ) {
		return function () use ( $count, $fn, $arguments, $accumulator ) {
			$oldArguments = $arguments;
			$arguments    = array_merge( $arguments, func_get_args() );

			$replacementIndex = count( $oldArguments );
			for ( $i = 0; $i < $replacementIndex; $i ++ ) {
				if ( count( $arguments ) <= $replacementIndex ) {
					break;
				}
				if ( $arguments[ $i ] === Fns::__ ) {
					$arguments[ $i ] = $arguments[ $replacementIndex ];
					unset( $arguments[ $replacementIndex ] );
					$arguments = array_values( $arguments );
				}
			}

			if ( ! in_array( Fns::__, $arguments, true ) && $count <= count( $arguments ) ) {
				return call_user_func_array( $fn, $arguments );
			}

			return $accumulator( $arguments );
		};
	};

	return $accumulator( [] );
}

/**
 * @param callable $fn
 *
 * @return Either
 */
function tryCatch( callable $fn ) {
	try {
		return Right::of( $fn() );
	} catch ( \Exception $e ) {
		return Either::left( $e );
	}
}