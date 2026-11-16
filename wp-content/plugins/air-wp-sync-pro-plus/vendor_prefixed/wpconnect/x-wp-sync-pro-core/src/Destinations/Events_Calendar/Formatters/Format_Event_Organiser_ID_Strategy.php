<?php
/**
 * Format URL to post id strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\Events_Calendar\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Format_Event_Organiser_ID_Strategy class
 */
class Format_Event_Organiser_ID_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_event_organiser_id';

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
				'formatted_value' => array(),
				'messages'        => array(),
			);
		}

		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}
		$value = array_reduce( $value, array( $this, 'recursive_split' ), array() );
		$value = array_map(
			function ( $v ) {
				return (int) $v;
			},
			$value
		);

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}

	/**
	 * Callback function used to recursively merge array, and split strings by commas
	 *
	 * @param  array        $carry  Array of values from the preceding iteration.
	 * @param  array|string $item  Current item.
	 * @return  array  $carry  Array of values after the current iteration.
	 */
	public function recursive_split( $carry, $item ) {
		if ( is_int( $item ) ) {
			$carry[] = (int) $item;
		}
		if ( is_string( $item ) ) {
			$carry = array_merge( $carry, array_map( 'trim', explode( ',', $item ) ) );
		}
		if ( is_array( $item ) ) {
			$carry = array_merge( $carry, $this->recursive_split( $carry, $item ) );
		}
		return $carry;
	}
}
