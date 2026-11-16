<?php
/**
 * Airtable format to string strategy.
 * (overrides default "format_to_string" strategy)
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;

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
	 * Markdown formatter
	 *
	 * @var Markdown_Formatter
	 */
	protected $markdown_formatter;

	/**
	 * Interval formatter.
	 *
	 * @var Interval_Formatter
	 */
	protected $interval_formatter;

	/**
	 * Constructor
	 *
	 * @param Markdown_Formatter $markdown_formatter Markdown formatter.
	 * @param Interval_Formatter $interval_formatter Interval formatter.
	 */
	public function __construct( $markdown_formatter, $interval_formatter ) {
		parent::__construct();

		$this->markdown_formatter = $markdown_formatter;
		$this->interval_formatter = $interval_formatter;
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
		$source_type  = $context->get_source_type();
		$source_field = $context->get_source_field();
		if ( 'richText' === $source_type ) {
			// Markdown.
			$value = $this->markdown_formatter->format( $value );
		} elseif ( in_array( $source_type, array( 'date', 'dateTime' ), true ) ) {
			// Date.
			$value = date_i18n( get_option( 'date_format' ), strtotime( $value ) );
		} elseif ( 'duration' === $source_type ) {
			$value = $this->interval_formatter->format( $value, $source_field );
		} elseif ( 'checkbox' === $source_type ) {
			// Checkbox.
			// Convert boolean to 0|1.
			$value = (int) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		} elseif ( is_array( $value ) ) {
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
