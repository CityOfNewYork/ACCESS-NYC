<?php

namespace BulkWP\BulkDelete\Core\Metas;

use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Module for Deleting Meta fields.
 *
 * @since 6.0.0
 */
abstract class MetasModule extends BaseModule {
	protected $item_type = 'metas';

	protected function render_restrict_settings( $item = 'posts' ) {
		bd_render_restrict_settings( $this->field_slug, $item );
	}

	/**
	 * Handle common filters.
	 *
	 * @param array $request Request array.
	 *
	 * @return array User options.
	 */
	protected function parse_common_filters( $request ) {
		$options = array();

		$options['restrict']     = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_restrict', false );
		$options['limit_to']     = absint( bd_array_get( $request, 'smbd_' . $this->field_slug . '_limit_to', 0 ) );
		$options['force_delete'] = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_force_delete', false );

		if ( $options['restrict'] ) {
			$options['date_op'] = bd_array_get( $request, 'smbd_' . $this->field_slug . '_op' );
			$options['days']    = absint( bd_array_get( $request, 'smbd_' . $this->field_slug . '_days' ) );
		}

		return $options;
	}

	/**
	 * Get the Post type and status args.
	 *
	 * @param string $post_type_and_status Post type and status.
	 *
	 * @since 6.0.1
	 *
	 * @return array Args.
	 */
	protected function get_post_type_and_status_args( $post_type_and_status ) {
		$type_status = $this->split_post_type_and_status( $post_type_and_status );

		return array(
			'post_type'   => $type_status['type'],
			'post_status' => $type_status['status'],
		);
	}
}
