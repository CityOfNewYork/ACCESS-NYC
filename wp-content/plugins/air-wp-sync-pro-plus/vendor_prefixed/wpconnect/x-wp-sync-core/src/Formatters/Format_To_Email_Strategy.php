<?php
/**
 * Format to email strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Format_To_Email_Strategy class
 */
class Format_To_Email_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_email';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception Format_To_Email_Strategy->format: a string is expected, received: ...
	 * @throws \Exception Format_To_Email_Strategy->format: an email is expected, received: ...
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
			throw new \Exception( esc_html( sprintf( 'Format_To_Email_Strategy->format: a string is expected, received: %s', $str_value ) ), 400 );
		}

		$value = trim( $value );

		if ( ! is_email( $value ) ) {
			throw new \Exception( esc_html( sprintf( 'Format_To_Email_Strategy->format: an email is expected, received: %s', $value ) ), 400 );
		}

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
