<?php

namespace Gravity_Forms\Gravity_SMTP\Migration;

class Migrator_Collection {

	/**
	 * @var Migrator[]
	 */
	private $migrators = array();

	public function add( $key, $migrator ) {
		$this->migrators[ $key ] = $migrator;
	}

	public function remove( $key ) {
		unset( $this->migrators[ $key ] );
	}

	public function get( $key ) {
		return isset( $this->migrators[ $key ] ) ? $this->migrators[ $key ] : null;
	}

	public function run_all() {
		foreach( $this->migrators as $key => $migrator ) {
			$migrator->migrate();
		}
	}

	public function run( $key ) {
		if ( ! isset( $this->migrators[ $key ] ) ) {
			return;
		}

		$this->migrators[ $key ]->migrate();
	}

}