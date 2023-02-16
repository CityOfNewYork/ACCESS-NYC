<?php

namespace WPML\Upgrade;

class CommandsStatus {
	const OPTION_KEY = 'wpml_update_statuses';

	/**
	 * @param string $className
	 *
	 * @return bool
	 */
	public function hasBeenExecuted( $className ) {
		return (bool) $this->get_update_option_value( $this->get_command_id( $className ) );
	}

	/**
	 * @param string $className
	 * @param bool   $flag
	 */
	public function markAsExecuted( $className, $flag = true ) {
		$this->set_update_status( $this->get_command_id( $className ), $flag );
		wp_cache_flush();
	}

	/**
	 * @param string $className
	 *
	 * @return string
	 */
	private function get_command_id( $className ) {
		return str_replace( '_', '-', strtolower( $className ) );
	}

	private function set_update_status( $id, $value ) {
		$update_options        = get_option( self::OPTION_KEY, array() );
		$update_options[ $id ] = $value;
		update_option( self::OPTION_KEY, $update_options, true );
	}

	private function get_update_option_value( $id ) {
		$update_options = get_option( self::OPTION_KEY, array() );

		if ( $update_options && array_key_exists( $id, $update_options ) ) {
			return $update_options[ $id ];
		}

		return null;
	}
}
