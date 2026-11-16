<?php
/**
 * Remote format to post status strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Post_Helpers;

/**
 * Format_To_Post_Status_Strategy class
 */
class Format_To_Post_Status_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_to_post_status';

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

		if ( ! is_string( $value ) || ! $this->is_available_post_stati( $value ) ) {
			try {
				$str_value = strval( $value );
			} catch ( \Throwable $throwable ) {
				$str_value = gettype( $value );
			}
			return array(
				'success'         => false,
				'formatted_value' => '',
				'messages'        => array( sprintf( 'Format_To_Post_Status_Strategy->format: The value "%s" is not a valid post status.', $str_value ) ),
			);
		}

		return array(
			'success'         => true,
			'formatted_value' => $value,
			'messages'        => array(),
		);
	}

	/**
	 * Return true if the post status is in the available list.
	 *
	 * @param string $value The post status to check.
	 *
	 * @return bool
	 */
	protected function is_available_post_stati( $value ) {
		$post_stati = Post_Helpers::get_post_stati();
		foreach ( $post_stati as $post_status ) {
			if ( $post_status['value'] === $value && $post_status['enabled'] ) {
				return true;
			}
		}

		// Special case that requires extra code in the imported (wp_trash_post...).
		if ( 'trash' === $value ) {
			return true;
		}

		return false;
	}
}
