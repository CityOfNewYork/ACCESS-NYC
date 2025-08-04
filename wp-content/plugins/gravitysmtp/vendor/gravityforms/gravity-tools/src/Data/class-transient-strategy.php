<?php

namespace Gravity_Forms\Gravity_Tools\Data;

class Transient_Strategy {

	/**
	 * Get transient value.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		return get_transient( $key );
	}

	/**
	 * Set transient value.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 * @param $value
	 * @param $timeout
	 *
	 * @return bool
	 */
	public function set( $key, $value, $timeout ) {
		return set_transient( $key, $value, $timeout );
	}

	/**
	 * Delete transient value.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function delete( $key ) {
		return delete_transient( $key );
	}

}