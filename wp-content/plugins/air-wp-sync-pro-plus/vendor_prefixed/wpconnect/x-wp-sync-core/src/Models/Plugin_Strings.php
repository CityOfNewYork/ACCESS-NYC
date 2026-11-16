<?php
/**
 * Allow to store plugin specific strings.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models;

/**
 * Class Plugin_Strings
 */
class Plugin_Strings {

	/**
	 * Plugin’s strings.
	 *
	 * @var string[]
	 */
	protected $strings;

	/**
	 * Constructor.
	 *
	 * @param string[] $strings Plugin’s strings.
	 */
	public function __construct( $strings ) {
		$this->strings = $strings;
	}

	/**
	 * Returns plugins’s strings.
	 *
	 * @return string[]
	 */
	public function get_plugin_strings(): array {
		return $this->strings;
	}

	/**
	 * Check if string is defined by $string_key parameter.
	 *
	 * @param string $string_key String key.
	 *
	 * @return bool
	 */
	public function has_string( $string_key ): bool {
		return isset( $this->strings[ $string_key ] );
	}

	/**
	 * Return string defined by $string_key parameter.
	 *
	 * @param string $string_key String key.
	 *
	 * @return string
	 * @throws \Exception The string %s is not set in Plugin_Strings class.
	 */
	public function get_string( $string_key ): string {
		if ( ! isset( $this->strings[ $string_key ] ) ) {
			throw new \Exception( esc_html( sprintf( 'The string %s is not set in Plugin_Strings class.', $string_key ) ) );
		}
		return $this->strings[ $string_key ];
	}

	/**
	 * Set string.
	 *
	 * @param string $string_key String key.
	 * @param string $string_value String value.
	 *
	 * @return self
	 */
	public function set_string( $string_key, $string_value ) {
		$this->strings[ $string_key ] = $string_value;
		return $this;
	}
}
