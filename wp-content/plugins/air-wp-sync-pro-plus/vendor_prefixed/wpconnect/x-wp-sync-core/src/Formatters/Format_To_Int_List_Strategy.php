<?php
/**
 * Format to int list strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_To_Int_List_Strategy class
 */
class Format_To_Int_List_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_int_list';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( empty( $value ) && 0 !== $value && '0' !== $value ) {
			return array(
				'success'         => true,
				'formatted_value' => array(),
				'messages'        => array(),
			);
		}
		$original_value = $value;

		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		$value = array_map(
			function ( $item_value ) {
				if ( is_scalar( $item_value ) ) {
					$item_value = (int) $item_value;
				}
				return $item_value;
			},
			$value
		);

		$all_int = array_reduce(
			$value,
			function ( $carry, $item ) {
				return $carry && is_integer( $item );
			},
			true
		);
		if ( ! $all_int ) {
			return array(
				'success'         => false,
				'formatted_value' => array(),
				'messages'        => array(
					sprintf( 'Format_To_Int_List_Strategy->format: The value can’t be converted to a list of integers, "%s" found.', gettype( $original_value ) ),
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
