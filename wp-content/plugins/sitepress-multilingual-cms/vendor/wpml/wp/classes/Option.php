<?php

namespace WPML\LIB\WP;

use WPML\Collect\Support\Traits\Macroable;
use function WPML\FP\curryN;
use function WPML\FP\partialRight;

/**
 * @method static callable|mixed get( ...$name ) - Curried :: string → mixed
 * @method static callable|mixed getRaw( ...$name ) - Curried :: string → null|string
 * @method static callable|mixed getOr( ...$name, ...$default ) - Curried :: string → mixed → mixed
 * @method static callable|mixed attemptSerializedRecovery( ...$name, ...$default ) - Curried :: string → mixed → mixed
 * @method static callable|bool update( ...$name, ...$value ) - Curried :: string → mixed → bool
 * @method static callable|bool updateWithoutAutoLoad( ...$name, ...$value ) - Curried :: string → mixed → bool
 * @method static callable|bool delete( ...$name ) - Curried :: string → bool
 */
class Option {
	use Macroable;

	/**
	 * @return void
	 */
	public static function init() {
		self::macro( 'get', curryN( 1, 'get_option' ) );
		self::macro( 'getOr', curryN( 2, 'get_option' ) );

		self::macro( 'update', curryN( 2, 'update_option' ) );
		self::macro( 'updateWithoutAutoLoad', curryN( 2, partialRight( 'update_option', false ) ) );

		self::macro( 'delete', curryN( 1, 'delete_option' ) );

		/**
		 * Returns a raw option value without running WP hooks and no parsing on the value.
		 */
		self::macro( 'getRaw', curryN( 1, function( $name ) {
			global $wpdb;
			$p = $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name );
			return $wpdb->get_var( $p ) ?: null;
		} ) );

		/**
		 * This method will attempt to recover an option that is a serialized object but it has been corrupted.
		 * It will adjust the string length of the serialized object to match the actual string length.
		 *
		 * If it's not recoverable, it will return the default value.
		 *
		 * @see is_serialized
		 */
		self::macro( 'attemptSerializedRecovery', curryN( 1, function( $name, $default ) {
			$value = $default;
			$dbValue = self::getRaw( $name );
			if ( $dbValue && is_serialized( $dbValue ) ) {
				$dbValueRecoveringStringIntegrityChecks = preg_replace_callback(
					'/(?<=^|\{|;)s:(\d+):[\"|\'](.*?)[\"|\'];(?=[asbdiO]\:\d|N;|\}|$)/s',
					function($m){
						return 's:' . strlen($m[2]) . ':"' . $m[2] . '";';
					},
					$dbValue
				);
				$restoredValue = maybe_unserialize( $dbValueRecoveringStringIntegrityChecks );
				if ( ( !is_string($restoredValue) || $dbValueRecoveringStringIntegrityChecks != $restoredValue ) && $restoredValue !== false ) {
					$value = $restoredValue;
				}
				self::update( $name, $value );
			}
			return $value;
		} ) );
	}

	/**
	 * This function is used to get an option value from the database
	 * attempting to restore it if the option is serialized but returns false.
	 *
	 * Also, this method will return the $default value when the option is corrupted.
	 *
	 * @method static callable|mixed getOrAttemptRecovery( ...$name, ...$default ) - Curried :: string → mixed → mixed
	 */
	public static function getOrAttemptRecovery( $name = null, $default = null ) {
		return call_user_func_array( curryN( 2,
			function( $name, $default ) {
				$value = self::get( $name );
				if ( $value === false ) {
					// Attempt to recovery a serialized object which keys has been corrupted.
					// This is useful where user may run scripts that modify the database strings manually,
					// like for example a replacement of URL.
					return self::attemptSerializedRecovery($name, $default);
				}
				return $value;
			}), func_get_args() );
	}
}

Option::init();
