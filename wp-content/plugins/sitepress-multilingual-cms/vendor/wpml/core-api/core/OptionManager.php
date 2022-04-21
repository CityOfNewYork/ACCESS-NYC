<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\WP;

use function WPML\FP\curryN;

class OptionManager {

	private $group_keys_key = 'WPML_Group_Keys';

	/**
	 * Get a WordPress option that is stored by group.
	 *
	 * @param string $group
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public function get( $group, $key, $default = false ) {
		$data = get_option( $this->get_key( $group ), array() );

		return isset( $data[ $key ] ) ? $data[ $key ] : $default;
	}

	/**
	 * Save a WordPress option that is stored by group
	 * The value is then stored by key in the group.
	 *
	 * eg. set( 'TM-wizard', 'complete', 'true' ) will create or add to the option WPML(TM-wizard)
	 * The dat in the option will then be an array of items stored by key.
	 *
	 * @param string $group
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $autoload
	 */
	public function set( $group, $key, $value, $autoload = true ) {
		$group_key = $this->get_key( $group );

		$data         = get_option( $group_key, array() );
		$data[ $key ] = $value;
		update_option( $group_key, $data, $autoload );

		$this->store_group_key( $group_key );
	}

	/**
	 * @param string $group
	 *
	 * @return string
	 */
	private function get_key( $group ) {
		return 'WPML(' . $group . ')';
	}

	/**
	 * @param string $group_key
	 */
	private function store_group_key( $group_key ) {
		$group_keys   = get_option( $this->group_keys_key, array() );
		$group_keys[] = $group_key;
		update_option( $this->group_keys_key, array_unique( $group_keys ) );
	}

	/**
	 * Returns all the options that need to be deleted on WPML reset.
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function reset_options( $options ) {
		$options[] = $this->group_keys_key;

		return array_merge( $options, get_option( $this->group_keys_key, array() ) );
	}

	/**
	 * Curried :: string → string → a → void
	 * @param string|null $group
	 * @param string|null $key
	 * @param mixed|null $value
	 *
	 * @return callable|void
	 */
	public static function updateWithoutAutoLoad( $group = null, $key = null, $value = null ) {
		$update = function ( $group, $key, $value ) {
			( new OptionManager() )->set( $group, $key, $value, false );
		};

		return call_user_func_array( curryN( 3, $update ), func_get_args() );
	}

	/**
	 * Curried :: string → string → a → void
	 * @param string|null $group
	 * @param string|null $key
	 * @param mixed|null $value
	 *
	 * @return callable|void
	 */
	public static function update( $group = null, $key = null, $value = null ) {
		return call_user_func_array( curryN( 3, [ new OptionManager(), 'set' ] ), func_get_args() );
	}

	/**
	 * Curried :: a → string → string → b
	 * @param mixed|null $default
	 * @param string|null $group
	 * @param string|null $key
	 *
	 * @return callable|mixed
	 */
	public static function getOr( $default = null, $group = null, $key = null ) {
		$get = function ( $default, $group, $key ) {
			return ( new OptionManager() )->get( $group, $key, $default );
		};

		return call_user_func_array( curryN( 3, $get ), func_get_args() );
	}
}
