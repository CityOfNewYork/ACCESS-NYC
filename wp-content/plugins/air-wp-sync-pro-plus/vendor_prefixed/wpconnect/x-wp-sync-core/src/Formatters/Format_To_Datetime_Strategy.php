<?php
/**
 * Format to datetime strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Format_To_Datetime_Strategy class
 */
class Format_To_Datetime_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_datetime';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( empty( $value ) || empty( $value->date_string ) ) {
			return array(
				'success'         => true,
				'formatted_value' => '',
				'messages'        => array(),
			);
		}

		if ( strtotime( $value->date_string ) ) {
			$time_zone = $value->include_time ? $value->time_zone : null;
			if ( $value->date_format ) {
				$value = \DateTimeImmutable::createFromFormat( $value->date_format, $value->date_string, $time_zone );
			} else {
				$value = new \DateTimeImmutable( $value->date_string, $time_zone );
			}

			if ( $value instanceof \DateTimeInterface ) {
				$value = $value->setTimezone( wp_timezone() );
			}
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
					sprintf( 'Format_To_Datetime_Strategy->format: The value is not a valid date, "%s" found.', $str_value ),
				),
			);
		}

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
