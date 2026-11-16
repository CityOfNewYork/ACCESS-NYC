<?php
/**
 * Format URL to post id strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_URL_To_Post_Id_Strategy class
 */
class Format_URL_To_Post_Id_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_url_to_post_id';

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
		if ( empty( $value ) ) {
			return array(
				'success'         => true,
				'formatted_value' => $value,
				'messages'        => array(),
			);
		}
		if ( ! is_string( $value ) || sanitize_url( $value, array( 'https', 'http' ) ) !== $value ) {
			throw new \Exception( esc_html( 'Format_URL_To_Post_Id_Strategy->format: the value should be a valid URL.' ), 400 );
		}
		$post_id = url_to_postid( $value );
		if ( 0 === $post_id ) {
			throw new \Exception( esc_html( sprintf( 'Format_URL_To_Post_Id_Strategy->format: no post found for the URL "%s".', $value ) ), 400 );
		}
		return array(
			'success'         => true,
			'formatted_value' => $post_id,
			'messages'        => array(),
		);
	}
}
