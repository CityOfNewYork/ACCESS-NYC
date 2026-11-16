<?php
/**
 * Format to plain text strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_To_Plain_Text_Strategy class
 */
class Format_To_Plain_Text_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_plain_text';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( ! is_string( $value ) ) {
			return array(
				'success'         => false,
				'formatted_value' => '',
				'messages'        => array( 'Format_To_Plain_Text_Strategy->format: the value should be a string.' ),
			);
		}

		$value = wp_strip_all_tags( $value );

		return array(
			'success'         => is_string( $value ),
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
