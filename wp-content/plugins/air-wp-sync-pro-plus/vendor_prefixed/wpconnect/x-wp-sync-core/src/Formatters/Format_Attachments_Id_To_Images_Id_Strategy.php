<?php
/**
 * Format attachments id to images id strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_Attachments_Id_To_Images_Id_Strategy class
 */
class Format_Attachments_Id_To_Images_Id_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_attachments_id_to_images_id';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 */
	public function format( $value, $context ) {
		if ( ! is_array( $value ) ) {
			return array(
				'success'         => false,
				'formatted_value' => array(),
				'messages'        => array( 'Format_Attachments_Id_to_Images_Id_Strategy->format: the value should be an array.' ),
			);
		}

		$messages = array();

		$was_empty        = empty( $value );
		$image_mime_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$value            = array_filter(
			$value,
			function ( $attachment_id ) use ( $image_mime_types ) {
				return in_array( get_post_mime_type( $attachment_id ), $image_mime_types, true );
			}
		);

		// Reset index.
		$value = array_values( $value );

		// If there were attachments but no one is an image there is certainly an issue.
		if ( ! $was_empty && empty( $value ) ) {
			$source = '';
			if ( $context->has_source_field() ) {
				$source .= ' Field id: ' . $context->get_source_field()->id;
			}
			if ( $context->has_existing_object_id() ) {
				$source .= ' Object id: ' . $context->get_existing_object_id();
			}

			$messages[] = sprintf( 'Format_Attachments_Id_to_Images_Id_Strategy->format: No image found in "%s"', $source );
		}

		return array(
			'success'         => is_array( $value ),
			'formatted_value' => $value,
			'messages'        => $messages,
		);
	}
}
