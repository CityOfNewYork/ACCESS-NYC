<?php
/**
 * Datetime to ISO 8601 DateTime string strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_Datetime_To_ISO8601_Strategy class
 */
class Format_Datetime_To_ISO8601_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_datetime_to_iso8601';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => '',
				'messages'        => array(),
			);
		}

		if ( ! ( $value instanceof \DateTimeInterface ) ) {
			try {
				$str_value = strval( $value );
			} catch ( \Throwable $throwable ) {
				$str_value = gettype( $value );
			}
			return array(
				'success'         => false,
				'formatted_value' => '',
				'messages'        => array(
					sprintf( 'Datetime_To_ISO8601_Strategy->format: The value is not a valid Datetime object, "%s" found.', $str_value ),
				),
			);
		}

		$value = $value->format( 'Y-m-d H:i:s' );

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
