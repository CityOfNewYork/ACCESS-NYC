<?php

namespace Gravity_Forms\Gravity_SMTP\Migration;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Data_Store\Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Data_Store\Plugin_Opts_Data_Store;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;

class Migrator {

	/**
	 * @var Migration[][]
	 */
	private $connector = array();

	/**
	 * @var Migration[]
	 */
	private $plugin = array();

	/**
	 * @var Migration[]
	 */
	private $user = array();

	public function add_connector_migration( $key, $og_id, $new_id ) {
		$migration               = new Migration( $og_id, $new_id );
		$this->connector[ $key ][] = $migration;
	}

	public function add_plugin_migration( $key, $og_id, $new_id ) {
		$migration            = new Migration( $og_id, $new_id );
		$this->plugin[ $key ] = $migration;
	}

	public function add_user_migration( $key, $og_id, $new_id, $user_id = 0 ) {
		if ( is_string( $new_id ) ) {
			$new_id = $this->default_user_action( $new_id, $user_id );
		}

		$migration          = new Migration( $og_id, $new_id );
		$this->user[ $key ] = $migration;
	}

	public function migrate() {
		foreach ( $this->connector as $connector => $migrations ) {
			foreach( $migrations as $conn_migration ) {
				$conn_migration->migrate();
			}
		}

		foreach ( $this->plugin as $key => $plugin_migration ) {
			$plugin_migration->migrate();
		}

		foreach ( $this->user as $key => $user_migration ) {
			$user_migration->migrate();
		}
	}

	public function migrate_single( $type, $key ) {
		if ( ! isset( $this->$type[ $key ] ) ) {
			return;
		}

		$this->$type[ $key ]->migrate();
	}

	public function migrate_connections() {
		foreach ( $this->connector as $key => $conn_migration ) {
			$conn_migration->migrate();
		}
	}

	public function migrate_plugins() {
		foreach ( $this->plugin as $key => $plugin_migration ) {
			$plugin_migration->migrate();
		}
	}

	public function migrate_users() {
		foreach ( $this->user as $key => $user_migration ) {
			$user_migration->migrate();
		}
	}

	private function default_user_action( $new_option_name, $user_id ) {
		return function ( $new_value ) use ( $new_option_name, $user_id ) {
			update_user_meta( $user_id, $new_option_name, $new_value );
		};
	}

}