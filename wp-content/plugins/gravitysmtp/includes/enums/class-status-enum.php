<?php

namespace Gravity_Forms\Gravity_SMTP\Enums;

class Status_Enum {

	/**
	 * Get the label for a given status key.
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
	 * Get the indicator type for a given status key.
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
			'pending'    => 'warning',
			'sent'       => 'active',
			'failed'     => 'error',
			'sandboxed'  => 'warning',
			'suppressed' => 'warning',
		);
	}

	private static function get_labels() {
		return array(
			'pending'    => __( 'Pending', 'gravitysmtp' ),
			'sent'       => __( 'Sent', 'gravitysmtp' ),
			'failed'     => __( 'Failed', 'gravitysmtp' ),
			'sandboxed'  => __( 'Sandboxed', 'gravitysmtp' ),
			'suppressed' => __( 'Suppressed', 'gravitysmtp' ),
		);
	}

}
