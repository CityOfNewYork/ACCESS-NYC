<?php
/**
 * Format attachments to attachments id strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Format_Attachments_to_Attachments_Id_Strategy class
 */
class Format_Attachments_To_Attachments_Id_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_attachments_to_attachments_id';

	/**
	 * Attachment formatter.
	 *
	 * @var Attachments_Formatter
	 */
	protected $attachment_formatter;

	/**
	 * Constructor
	 *
	 * @param Attachments_Formatter $attachment_formatter Attachment formatter.
	 */
	public function __construct( $attachment_formatter ) {
		parent::__construct();

		$this->attachment_formatter = $attachment_formatter;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception Format_Attachments_to_Attachments_Id_Strategy->format: importer is required in the context.
	 */
	public function format( $value, $context ) {
		if ( ! $context->has_importer() ) {
			throw new \Exception( 'Format_Attachments_to_Attachments_Id_Strategy->format: importer is required in the context.', 400 );
		}
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => array(),
				'messages'        => array(),
			);
		}
		if ( ! is_array( $value ) && ! is_object( $value ) ) {
			return array(
				'success'         => false,
				'formatted_value' => array(),
				'messages'        => array( 'Format_Attachments_to_Attachments_Id_Strategy->format: the value is neither an array or an object.' ),
			);
		}

		$post_id = $context->has_arg( 'attach_to_post_id' ) ? $context->get_arg( 'attach_to_post_id' ) : null;

		// Import as media and get attachment ID.
		$value = $this->attachment_formatter->format( $value, $context->get_importer(), $post_id );

		return array(
			'success'         => is_array( $value ),
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
