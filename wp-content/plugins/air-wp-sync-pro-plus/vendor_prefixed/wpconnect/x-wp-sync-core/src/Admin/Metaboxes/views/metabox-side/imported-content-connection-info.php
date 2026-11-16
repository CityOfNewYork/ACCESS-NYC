<?php
/**
 * Display imported content connection details.
 *
 * @package wpconnect/x-wp-sync-core
 */

return function ( $content_imported_metas, $connection, $connection_link, $disconnect_url ) {
	$updated_at = $content_imported_metas->get_updated_at()->setTimezone( wp_timezone() );
	?>
	<p>
		<strong><?php esc_html_e( 'Connection: ', 'air-wp-sync-pro-plus' ); ?></strong>
		<a href="<?php echo esc_url( $connection_link ); ?>"
			target="_blank"><?php echo esc_html( $connection->post_title ); ?></a>
	</p>
	<p>
		<strong><?php esc_html_e( 'Record id: ', 'air-wp-sync-pro-plus' ); ?></strong><?php echo esc_html( $content_imported_metas->get_record_id() ); ?>
	</p>
	<p>
		<strong><?php esc_html_e( 'Updated at: ', 'air-wp-sync-pro-plus' ); ?></strong><?php echo esc_html( $updated_at->format( get_option( 'date_format' ) ) . ' ' . $updated_at->format( get_option( 'time_format' ) ) ); ?>
	</p>
	<a class="button-link button-link-delete"
		href="<?php echo esc_url( $disconnect_url ); ?>"
	><?php esc_html_e( 'Disconnect from this connection', 'air-wp-sync-pro-plus' ); ?></a>
	<div class="howto"
		style="margin-top: 0.5em;"><?php esc_html_e( 'Disconnecting this content will allow you update it from another connection', 'air-wp-sync-pro-plus' ); ?></div>
	<?php
};
