<?php
/**
 * Format to URL strategy.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;

/**
 * ACF_Format_To_URL_Strategy class
 */
class ACF_Format_To_URL_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'acf_format_to_url';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception ACF_Format_To_URL_Strategy->format: a string is expected, received: ...
	 * @throws \Exception ACF_Format_To_URL_Strategy->format: a URL is expected, received: ...
	 */
	public function format( $value, $context ) {
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => '',
				'messages'        => array(),
			);
		}
		if ( ! is_string( $value ) ) {
			try {
				$str_value = strval( $value );
			} catch ( \Throwable $throwable ) {
				$str_value = gettype( $value );
			}
			throw new \Exception( esc_html( sprintf( 'ACF_Format_To_URL_Strategy->format: a string is expected, received: %s', $str_value ) ), 400 );
		}

		// Check based on acf_field_url::validate_value.
		if ( strpos( $value, '://' ) === false && strpos( $value, '//' ) !== 0 ) {
			throw new \Exception( esc_html( sprintf( 'ACF_Format_To_URL_Strategy->format: a URL is expected, received: %s', $value ) ), 400 );
		}

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
