<?php

namespace WPML\FP;

use WPML\Collect\Support\Traits\Macroable;

/**
 * @method static callable|mixed inspect( mixed ...$input )
 * @method static callable|mixed log( string ...$label )
 * @method static callable|mixed logDump( string ...$label, mixed ...$input )
 * @method static callable|mixed logPrintR( string ...$label, mixed ...$input )
 * @method static callable|mixed logBacktrace( string ...$label, mixed ...$input )
 */
class Debug {

	use Macroable;

	public static function init() {

		self::macro( 'inspect', curryN( 1, function( $input ) {
			$allParameters = func_get_args();
			function_exists( 'xdebug_break' ) && xdebug_break();
			return $input;
		} ) );

		self::macro( 'log', curryN( 2, function( $string, $input ) {
			error_log( $string );
			return $input;
		} ) );

		self::macro( 'logDump', curryN( 2, function( $label, $input ) {
			ob_start();
			var_dump( $input );
			$dumpInput = ob_get_clean();
			error_log( $label . ': ' . $dumpInput );
			return $input;
		} ) );

		self::macro( 'logPrintR', curryN( 2, function( $label, $input ) {
			error_log( $label . ': ' . print_r( $input, true ) );
			return $input;
		} ) );

		self::macro( 'logBacktrace', curryN( 2, function( $label, $input ) {
			ob_start();
			debug_print_backtrace();
			$backtrace = ob_get_clean();
			error_log( $label . ': ' . PHP_EOL . $backtrace );
			return $input;
		} ) );
	}
}

Debug::init();
