<?php
/**
 * Airtable format to raw strategy.
 * (overrides default "format_to_raw" strategy)
 * If the source field value is already something WordPress can manage, don't change the format.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;

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
	 * Markdown formatter
	 *
	 * @var Markdown_Formatter
	 */
	protected $markdown_formatter;

	/**
	 * Formatter.
	 *
	 * @var Abstract_Formatter
	 */
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param Markdown_Formatter $markdown_formatter Markdown formatter.
	 * @param Abstract_Formatter $formatter Formatter.
	 */
	public function __construct( $markdown_formatter, $formatter ) {
		parent::__construct();

		$this->markdown_formatter = $markdown_formatter;
		$this->formatter          = $formatter;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception Format_To_Raw->format: source_field is required in the context.
	 */
	public function format( $value, $context ) {
		if ( ! $context->has_source_field() ) {
			throw new \Exception( 'Format_To_Raw_Strategy->format: source_field is required in the context.', 400 );
		}
		$source_type = $context->get_source_type();
		if ( 'richText' === $source_type ) {
			// Markdown.
			$value = $this->markdown_formatter->format( $value );
		} elseif ( 'multipleAttachments' === $source_type ) {
			// Attachments.
			$value = $this->formatter->format( 'format_attachments_to_attachments_id', $value, $context );
		} elseif ( 'checkbox' === $source_type ) {
			// Checkbox.
			// Convert boolean to 0|1.
			$value = (int) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		return array(
			'success'         => true,
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
