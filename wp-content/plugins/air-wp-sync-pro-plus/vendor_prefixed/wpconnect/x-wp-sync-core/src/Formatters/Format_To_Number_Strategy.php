<?php
/**
 * Format to number strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_To_Number_Strategy class
 */
class Format_To_Number_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_number';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( '' === $value || is_null( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => '',
				'messages'        => array(),
			);
		}

		if ( ! is_numeric( $value ) ) {
			return array(
				'success'         => false,
				'formatted_value' => '',
				'messages'        => array(
					sprintf( 'Format_To_Number_Strategy->format: The value should be numeric, "%s" found.', gettype( $value ) ),
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
