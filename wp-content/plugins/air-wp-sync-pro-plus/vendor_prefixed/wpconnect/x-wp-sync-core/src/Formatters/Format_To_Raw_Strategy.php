<?php
/**
 * Format to raw strategy.
 * If the source field value is already something WordPress can manage, don't change the format.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Format_To_Raw_Strategy class
 */
class Format_To_Raw_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_raw';

	/**
	 * Formatter.
	 *
	 * @var Abstract_Formatter
	 */
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param Abstract_Formatter $formatter Formatter.
	 *
	 * @throws \Exception Formatter strategy slug must be defined.
	 */
	public function __construct( $formatter ) {
		parent::__construct();

		$this->formatter = $formatter;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception Format_To_Raw_Strategy->format: source_field is required in the context.
	 */
	public function format( $value, $context ) {
		if ( ! $context->has_source_field() ) {
			throw new \Exception( 'Format_To_Raw_Strategy->format: source_field is required in the context.', 400 );
		}

		$source_type = $context->get_source_type();
		if ( 'files' === $source_type ) {
			$value = $this->formatter->format( 'format_attachments_to_attachments_id', $value, $context );
			$value = array_map(
				function ( $media_id ) {
					return wp_get_attachment_url( $media_id );
				},
				$value
			);
		}

		if ( is_array( $value ) ) {
			// Multiple values.
			$value = implode( ', ', $value );
		} else {
			// Default string.
			$value = strval( $value );
		}

		return array(
			'success'         => is_string( $value ),
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
