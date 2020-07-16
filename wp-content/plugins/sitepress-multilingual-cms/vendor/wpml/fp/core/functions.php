<?php

namespace WPML\FP;

/**
 * Wraps the given function and returns a function that can take arguments as an array and invokes
 * the wrapped function with individual arguments
 *
 * @param callable $fn
 *
 * @return \Closure
 */
function spreadArgs( callable $fn ) {
	return function ( $args ) use ( $fn ) {
		return $fn( ...$args );
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
 * Returns new function which applies each given function to the result of another from left to right
 * pipe(f, g, h)(x) is the same as h(g(f(x)))
 *
 * @param callable $f
 * @param callable $g
 *
 * @return callable
 */
function pipe( callable $f, callable $g ) {
	return call_user_func_array( 'WPML\FP\compose', array_reverse( func_get_args() ) );
}

/**
 * Returns new function which will behave like $function with
 * predefined left arguments passed to partial
 *
 * @param callable $function
 * @param mixed    $arg1
 * @param mixed ...
 *
 * @return callable
 */
function partial( callable $function, $arg1 ) {
	$args = array_slice( func_get_args(), 1 );

	return function () use ( $function, $args ) {
		return call_user_func_array( $function, array_merge( $args, func_get_args() ) );
	};
}

/**
 * Returns new partial function which will behave like $function with
 * predefined right arguments passed to partialRight
 *
 * @param callable $function
 * @param mixed    $arg1
 * @param mixed ...
 *
 * @return callable
 */
function partialRight( callable $function, $arg1 ) {
	$args = array_slice( func_get_args(), 1 );

	return function () use ( $function, $args ) {
		return call_user_func_array( $function, array_merge( func_get_args(), $args ) );
	};
}

function tap( callable $fn ) {
	return function ( $value ) use ( $fn ) {
		$fn( $value );

		return $value;
	};
}

;

function either( callable $f, callable $g ) {
	return function ( $value ) use ( $f, $g ) {
		return $f( $value ) || $g( $value );
	};
}

;

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

function curry( callable $fn, $required = true ) {
	if ( is_string( $fn ) && strpos( $fn, '::', 1 ) !== false ) {
		$reflection = new \ReflectionMethod( $fn );
	} else if ( is_array( $fn ) && count( $fn ) == 2 ) {
		$reflection = new \ReflectionMethod( $fn[0], $fn[1] );
	} else if ( is_object( $fn ) && method_exists( $fn, '__invoke' ) ) {
		$reflection = new \ReflectionMethod( $fn, '__invoke' );
	} else {
		$reflection = new \ReflectionFunction( $fn );
	}

	$count = $required ? $reflection->getNumberOfRequiredParameters() : $reflection->getNumberOfParameters();

	return curryN( $count, $fn );
}

function apply( $fnName ) {
	$args = array_slice( func_get_args(), 1 );

	return function ( $container ) use ( $fnName, $args ) {
		return call_user_func_array( [ $container, $fnName ], $args );
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
 * @param $fnName
 *
 * @return _Invoker
 */
function invoke( $fnName ) {
	return new _Invoker( $fnName );
}

function chain( callable $fn ) {
	return function ( $container ) use ( $fn ) {
		if ( method_exists( $container, 'chain' ) ) {
			return $container->chain( $fn );
		} elseif ( method_exists( $container, 'flatMap' ) ) {
			return $container->flatMap( $fn );
		} elseif ( method_exists( $container, 'join' ) ) {
			return $container->map( $fn )->join();
		} else {
			throw new \Exception( 'chainable method not found' );
		}
	};
}

function flatMap( callable $fn ) {
	return chain( $fn );
}

function tryCatch( callable $fn ) {
	try {
		return Right::of( $fn() );
	} catch ( \Exception $e ) {
		return Either::left( $e );
	}
}

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

;
