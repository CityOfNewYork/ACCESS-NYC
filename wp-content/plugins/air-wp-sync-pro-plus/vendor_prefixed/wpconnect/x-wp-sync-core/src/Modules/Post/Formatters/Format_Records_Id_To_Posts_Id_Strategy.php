<?php
/**
 * Remote format records id to posts id.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\Post\Post_Helpers;

/**
 * Format_Records_Id_To_Posts_Id_Strategy class
 */
class Format_Records_Id_To_Posts_Id_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_records_id_to_posts_id';

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
				'formatted_value' => array(),
				'messages'        => array(),
			);
		}
		if ( ! is_array( $value ) ) {
			return array(
				'success'         => false,
				'formatted_value' => array(),
				'messages'        => array( 'Format_Records_Id_To_Posts_Id_Strategy->format: the value should be an array.' ),
			);
		}

		$messages = array();

		$value = array_filter(
			$value,
			function ( $v ) use ( &$messages ) {
				if ( ! is_string( $v ) ) {
					$messages[] = 'Format_Records_Id_To_Posts_Id_Strategy->format: not a record id (' . gettype( $v ) . ')';
					return false;
				}
				return true;
			}
		);

		$objects_id = get_posts(
			array(
				'fields'      => 'ids',
				'post_type'   => 'any',
				'post_status' => 'any',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_air_wp_sync_record_id',
						'value'   => $value,
						'compare' => 'IN',
					),
				),
			)
		);

		$record_id_to_post_id = array();
		foreach ( $objects_id as $post_id ) {
			$record_id                          = get_post_meta( $post_id, '_air_wp_sync_record_id', true );
			$record_id_to_post_id[ $record_id ] = $post_id;
		}
		$final_ordered_list = array();
		$does_not_match     = array();
		foreach ( $value as $record_id ) {
			if ( isset( $record_id_to_post_id[ $record_id ] ) ) {
				$final_ordered_list[] = $record_id_to_post_id[ $record_id ];
			} else {
				$does_not_match[] = $record_id;
			}
		}
		if ( count( $does_not_match ) > 0 ) {
			$messages [] = sprintf( 'Format_Records_Id_To_Posts_Id_Strategy->format: not all records id match with a post id, records id: %s.', implode( ', ', $does_not_match ) );
		}

		return array(
			'success'         => true,
			'formatted_value' => $final_ordered_list,
			'messages'        => $messages,
		);
	}
}
