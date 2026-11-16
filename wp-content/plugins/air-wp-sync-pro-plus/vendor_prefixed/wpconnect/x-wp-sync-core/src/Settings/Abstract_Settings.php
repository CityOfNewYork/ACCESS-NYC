<?php
/**
 * Abstract Settings class.
 * Base class to define custom settings.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Settings;

/**
 * Class Abstract_Settings
 */
abstract class Abstract_Settings implements \JsonSerializable {
	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructor
	 *
	 * @param array $settings Settings.
	 */
	public function __construct( $settings ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get a setting
	 *
	 * @param string $key Setting key.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		return array_reduce(
			explode( '.', $key ),
			function ( $o, $p ) {
				return is_array( $o ) && array_key_exists( $p, $o ) ? $o[ $p ] : false;
			},
			$this->settings
		);
	}

	/**
	 * Set a setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 */
	public function set( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	/**
	 * Delete a setting
	 *
	 * @param string $key Setting key.
	 *
	 * @return void
	 */
	public function delete( $key ) {
		if ( array_key_exists( $key, $this->settings ) ) {
			unset( $this->settings[ $key ] );
		}
	}

	/**
	 * Get all settings as array
	 *
	 * @return array
	 */
	public function to_array() {
		return $this->settings;
	}

	/**
	 * Retrieves the response data for JSON serialization.
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->settings;
	}
}
