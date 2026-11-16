<?php
/**
 * Format to boolean strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Format_To_Boolean_Strategy class
 */
class Format_To_Boolean_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_boolean';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( is_bool( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => $value,
				'messages'        => array(),
			);
		}
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => false,
				'messages'        => array(),
			);
		}

		return array(
			'success'         => true,
			'formatted_value' => true,
			'messages'        => array(),
		);
	}
}
