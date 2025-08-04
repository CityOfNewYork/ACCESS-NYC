<?php

namespace Gravity_Forms\Gravity_Tools\Upgrades;

use Gravity_Forms\Gravity_Tools\Logging\Logger;

class Upgrade_Routines {

	/**
	 * @var string
	 */
	protected $namespace;

	protected $routines = array();

	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	public function add( $key, $callback ) {
		$this->routines[ $key ] = $callback;
	}

	public function remove( $key ) {
		unset( $this->routines[ $key ] );
	}

	public function get( $key ) {
		return isset( $this->routines[ $key ] ) ? $this->routines[ $key ] : null;
	}

	public function handle() {
		foreach( $this->routines as $key => $callback ) {
			$option_key = sprintf( '%s_upgrade_routine_%s', $this->namespace, $key );
			$handled = get_option( $option_key, false );

			if ( $handled ) {
				continue;
			}

			call_user_func( $callback );

			update_option( $option_key, true );
		}
	}


}