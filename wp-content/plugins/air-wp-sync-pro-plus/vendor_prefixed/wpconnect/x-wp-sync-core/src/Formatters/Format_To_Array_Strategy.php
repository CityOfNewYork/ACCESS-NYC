<?php
/**
 * Format to array strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_To_Array_Strategy class
 */
class Format_To_Array_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_array';

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

		return array(
			'success'         => true,
			'formatted_value' => ! is_array( $value ) ? array( $value ) : $value,
			'messages'        => array(),
		);
	}
}
