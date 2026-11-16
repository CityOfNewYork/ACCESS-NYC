<?php
/**
 * Format label to post status strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Post_Helpers;

/**
 * Format_Label_To_Post_Status_Strategy class
 */
class Format_Label_To_Post_Status_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_label_to_post_status';

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
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

		$post_stati = Post_Helpers::get_post_stati();
		foreach ( $post_stati as $post_status ) {
			if ( $post_status['label'] === $value ) {
				$value = $post_status['value'];
			}
		}

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}
}
