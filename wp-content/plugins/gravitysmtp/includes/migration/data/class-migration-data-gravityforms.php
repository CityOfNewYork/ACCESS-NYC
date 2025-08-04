<?php

namespace Gravity_Forms\Gravity_SMTP\Migration\Data;

use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Base;
use Gravity_Forms\Gravity_SMTP\Connectors\Connector_Service_Provider;
use Gravity_Forms\Gravity_SMTP\Gravity_SMTP;
use Gravity_Forms\Gravity_SMTP\Migration\Migrator;
use Gravity_Forms\Gravity_SMTP\Migration\Migrator_Collection;

class Migration_Data_Gravityforms {

	protected $connector_types = array(
		'postmark',
		'sendgrid',
		'mailgun',
	);

	public function get_migrations() {
		/**
		 * @var Migrator_Collection $collection
		 */
		$collection = new Migrator_Collection();

		$gf_migrator = new Migrator();

		foreach( $this->connector_types as $connector_name ) {
			$gf_migrator = $this->get_migrations_for_connector( $gf_migrator, $connector_name );
		}

		$collection->add( 'gravityforms', $gf_migrator );

		return $collection;
	}

	private function get_migrations_for_connector( Migrator $gf_migrator, $connector_name ) {
		$factory = Gravity_SMTP::container()->get( Connector_Service_Provider::CONNECTOR_FACTORY );
		$connector = $factory->create( $connector_name );
		$map       = $connector->migration_map();

		if ( empty( $map ) ) {
			return array();
		}

		foreach ( $map as $migration_data ) {
			$og_callback = function() use ( $migration_data ) {
				$original_key = isset( $migration_data['original_key'] ) ? $migration_data['original_key'] : '';
				$sub_key      = isset( $migration_data['sub_key'] ) ? $migration_data['sub_key'] : false;
				$transform    = isset( $migration_data['transform'] ) ? $migration_data['transform'] : false;

				if ( empty( $original_key ) ) {
					return '';
				}

				$value = get_option( $original_key );

				if ( $sub_key && is_array( $value ) ) {
					$value = rgars( $value, $sub_key );
				}

				if ( empty( $value ) ) {
					return '';
				}

				if ( $transform ) {
					$value = call_user_func( $transform, $value );
				}

				return $value;
			};

			$new_key = isset( $migration_data['new_key'] ) ? $migration_data['new_key'] : '';

			$gf_migrator->add_connector_migration( $connector_name, $og_callback, $this->default_connector_action( $new_key, $connector_name ) );
		}

		return $gf_migrator;
	}

	private function default_connector_action( $new_option_name, $connector ) {
		return function ( $new_value ) use ( $new_option_name, $connector ) {
			/**
			 * @var Opts_Data_Store $data
			 */
			$data = Gravity_SMTP::container()->get( Connector_Service_Provider::DATA_STORE_OPTS );
			$data->save( $new_option_name, $new_value, $connector );
		};
	}

}