<?php

namespace Gravity_Forms\Gravity_SMTP\Enums;

class Suppression_Reason_Enum {

	/**
	 * Get the label for a given suppression key.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function label( $key ) {
		$labels = self::get_labels();

		if ( ! isset( $labels[ $key ] ) ) {
			return $key;
		}

		return $labels[ $key ];
	}

	/**
	 * Get the indicator type for a given suppression key.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public static function indicator( $key ) {
		$indicators = self::get_status_map();

		if ( ! isset( $indicators[ $key ] ) ) {
			return 'error';
		}

		return $indicators[ $key ];
	}

	private static function get_status_map() {
		return array(
			'manually_added' => 'warning',
		);
	}

	private static function get_labels() {
		return array(
			'manually_added' => __( 'Manually Suppressed', 'gravitysmtp' ),
		);
	}

}
