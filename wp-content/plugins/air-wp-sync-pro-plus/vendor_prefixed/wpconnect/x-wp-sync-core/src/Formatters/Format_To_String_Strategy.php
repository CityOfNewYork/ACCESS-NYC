<?php
/**
 * Format to string strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Format_To_String_Strategy class
 */
class Format_To_String_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_string';

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
	 * @throws \Exception Format_To_String_Strategy->format: source_field is required in the context.
	 */
	public function format( $value, $context ) {
		if ( ! $context->has_source_field() ) {
			throw new \Exception( 'Format_To_String_Strategy->format: source_field is required in the context.', 400 );
		}

		$source_type = $context->get_source_type();
		if ( in_array( $source_type, array( 'date' ), true ) ) {
			/**
			 * "format_to_datetime" return a DateTimeImmutable object.
			 *
			 * @var \DateTimeImmutable $datetime_value
			 */
			$datetime_value = $this->formatter->format( 'format_to_datetime', $value, $context );
			$value          = $datetime_value->format( get_option( 'date_format' ) );
		} elseif ( 'files' === $source_type ) {
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
