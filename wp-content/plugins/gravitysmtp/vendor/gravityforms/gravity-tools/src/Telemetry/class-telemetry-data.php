<?php

namespace Gravity_Forms\Gravity_Tools\Telemetry;

/**
 * Class Telemetry_Data
 *
 * Base class for telemetry data.
 *
 * @package Gravity_Forms\Gravity_Forms\Telemetry
 */
abstract class Telemetry_Data {

	/**
	 * @var array $data Data to be sent.
	 */
	public $data = array();

	/**
	 * @var string $key Unique identifier for this data object.
	 */
	public $key = '';

	protected $enabled_setting_name = '';

	protected $data_setting_name = '';

	abstract public function after_send( $response );

	abstract public function record_data();

	/**
	 * Determine if the user has allowed data collection.
	 *
	 * @since 1.0.3
	 *
	 * @return false|mixed|null
	 */
	public function is_data_collection_allowed() {
		static $is_allowed;

		if ( ! is_null( $is_allowed ) ) {
			return $is_allowed;
		}

		$is_allowed = get_option( $this->enabled_setting_name, false );

		return $is_allowed;
	}

	/**
	 * Get the current telemetry data.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_existing_data() {
		return get_option( $this->data_setting_name, [] );
	}

	/**
	 * Save telemetry data.
	 *
	 * @since 1.0
	 *
	 * @param Telemetry_Data $data The data to save.
	 *
	 * @return void
	 */
	public function save_data( $data ) {
		$existing_data = $this->get_existing_data();

		if ( ! $existing_data ) {
			$existing_data = array();
		}

		$existing_data[ $this->key ] = $data;

		update_option( $this->data_setting_name, $existing_data, false );
	}
}
