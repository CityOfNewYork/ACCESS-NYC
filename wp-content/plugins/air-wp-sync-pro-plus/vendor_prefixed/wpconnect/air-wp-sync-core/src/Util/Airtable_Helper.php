<?php
/**
 * Airtable helper class.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Airtable_WP_Sync_Core\Util;

/**
 * Airtable class.
 */
class Airtable_Helper {
	/**
	 * Return true if the run started more than two hours ago
	 *
	 * @param   string $start_date    Date the run started.
	 * @param   string $current_date  Current date (mainly for tests purpose).
	 *
	 * @return  bool    true if the run started more than two hours ago
	 */
	public static function should_refresh_attachment_urls( $start_date, $current_date = null ) {
		if ( ! $start_date ) {
			return false;
		}
		if ( ! $current_date ) {
			$current_date = gmdate( 'Y-m-d H:i:s' );
		}
		try {
			$current_datetime              = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $current_date );
			$start_datetime_plus_two_hours = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $start_date )->add( new \DateInterval( 'PT2H' ) );

			if ( $current_datetime < $start_datetime_plus_two_hours ) {
				return false;
			}
		} catch ( \Throwable $exception ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns a list of attachment fields ids
	 *
	 * @param  array $fields  Array of table fields.
	 */
	public static function get_attachment_fields_id( $fields ) {
		return array_reduce(
			$fields,
			function ( $carry, $field ) {
				if ( 'multipleAttachments' === $field->type ) {
					$carry[] = $field->id;
				}
				return $carry;
			},
			array()
		);
	}
}
