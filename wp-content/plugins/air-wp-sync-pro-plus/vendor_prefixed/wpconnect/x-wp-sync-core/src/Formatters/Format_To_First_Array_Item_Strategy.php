<?php
/**
 * Format to first array item strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_To_First_Array_Item_Strategy class
 */
class Format_To_First_Array_Item_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_first_array_item';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( ! is_array( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => $value,
				'messages'        => array(),
			);
		}

		return array(
			'success'         => true,
			'formatted_value' => count( $value ) > 0 ? $value[0] : null,
			'messages'        => array(),
		);
	}
}
