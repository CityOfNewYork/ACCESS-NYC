<?php
/**
 * Interval Formatter.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters;

/**
 * Class Interval_Formatter
 */
class Interval_Formatter {

	/**
	 * Convert Interval to formatted text
	 *
	 * @param int|float $value Duration.
	 * @param object    $field Airtable field.
	 *
	 * @return string
	 */
	public function format( $value, $field ) {
		$airtable_format = isset( $field->options->durationFormat ) ? $field->options->durationFormat : '';

		$milliseconds_count = substr_count( $airtable_format, 'S' );
		$hours              = floor( $value / 3600 );
		$minutes            = floor( ( $value / 60 ) % 60 );
		$seconds            = $value % 60;
		$millsecs           = intval( ( $value - intval( $value ) ) * pow( 10, $milliseconds_count ) );

		$result = '';
		if ( $hours ) {
			$result .= $hours;
		} else {
			$result .= '0';
		}
		if ( $minutes ) {
			if ( strlen( $result ) ) {
				$result .= ':';
			}
			$result .= sprintf( '%02d', $minutes );
		} elseif ( strlen( $result ) ) {
			$result .= ':00';
		} else {
			$result .= '0';
		}

		if ( strpos( $airtable_format, 'h:mm:ss' ) > -1 ) {
			if ( $seconds ) {
				if ( strlen( $result ) ) {
					$result .= ':';
				}
				$result .= sprintf( '%02d', $seconds );
			} elseif ( strlen( $result ) ) {
				$result .= ':00';
			}
		}

		if ( strpos( $airtable_format, 'S' ) ) {
			if ( $millsecs ) {
				$result .= '.' . $millsecs;
			} else {
				$result .= '.' . sprintf( "%0{$milliseconds_count}d", $millsecs );
			}
		}

		return $result;
	}
}
