<?php

namespace WPML\ICLToATEMigration;

use WPML\FP\Obj;
use WPML\LIB\WP\Option;

class Data {

	const OPTION_KEY = 'wpml_icl_to_ate_migration';

	const MEMORY_MIGRATED = 'memory_migrated';
	const ICL_DEACTIVATED = 'icl-deactivated';
	const ICL_CREDENTIALS = 'icl-credentials';

	/**
	 * @param bool $flag
	 *
	 * @return void
	 */
	public static function setMemoryMigrated( $flag = true ) {
		self::save( self::MEMORY_MIGRATED, $flag );
	}

	/**
	 * @return bool
	 */
	public static function isMemoryMigrated() {
		return self::get( self::MEMORY_MIGRATED );
	}

	/**
	 * @param bool $flag
	 *
	 * @return void
	 */
	public static function setICLDeactivated( $flag = true ) {
		self::save( self::ICL_DEACTIVATED, $flag );
	}

	/**
	 * @return bool
	 */
	public static function isICLDeactivated() {
		return self::get( self::ICL_DEACTIVATED );
	}

	/**
	 * @param array $credentials
	 *
	 * @return void
	 */
	public static function saveICLCredentials( array $credentials ) {
		self::save( self::ICL_CREDENTIALS, $credentials );
	}

	/**
	 * @return array
	 */
	public static function getICLCredentials() {
		return self::get( self::ICL_CREDENTIALS );
	}

	private static function save( $name, $value ) {
		$options          = Option::getOr( self::OPTION_KEY, [] );
		$options[ $name ] = $value;

		Option::update( self::OPTION_KEY, $options );
	}

	private static function get( $name ) {
		return Obj::propOr( false, $name, Option::getOr( self::OPTION_KEY, [] ) );
	}
}
