<?php
/**
 * Format path to URL strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_Path_To_URL_Strategy class
 */
class Format_Path_To_URL_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_path_to_url';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @throws \Exception Format_Path_To_URL_Strategy->format: a string is expected, received: %s.
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => '',
				'messages'        => array(),
			);
		}
		if ( ! is_string( $value ) ) {
			try {
				$str_value = strval( $value );
			} catch ( \Throwable $throwable ) {
				$str_value = gettype( $value );
			}
			throw new \Exception( esc_html( sprintf( 'Format_Path_To_URL_Strategy->format: a string is expected, received: %s', $str_value ) ), 400 );
		}

		return array(
			'success'         => true,
			'formatted_value' => home_url( $value ),
			'messages'        => array(),
		);
	}
}
