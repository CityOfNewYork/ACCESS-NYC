<?php
/**
 * Display connection state: status, last error, last updated date time, next sync.
 *
 * @package wpconnect/x-wp-sync-core
 */

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Connection state infos.
 *
 * @param array  $data  {
 *      data to display.
 *  @type string $status Status.
 *  @type array $errors Errors.
 *  @type int|string $last_updated Last updated date.
 *  @type int|string $next_sync Next sync.
 *  @type string $status_class Status class.
 *  @type string $content_ids Processed content ids.
 *  @type string $count_processed  Number of processed items.
 *  @type string $count_deleted  Number of deleted items. Not used yet ;).
 * }
 */
return function ( $data ) {
	?>
	<h4><?php esc_html_e( 'Last Sync Status', 'air-wp-sync-pro-plus' ); ?></h4>

	<p class="<?php echo esc_attr( $data['status_class'] ); ?>">
		<?php if ( 'success' === $data['status'] ) : ?>
			<?php esc_html_e( 'Successful!', 'air-wp-sync-pro-plus' ); ?>
		<?php elseif ( 'error' === $data['status'] ) : ?>
			<?php esc_html_e( 'Error', 'air-wp-sync-pro-plus' ); ?>
		<?php elseif ( 'cancel' === $data['status'] ) : ?>
			<?php esc_html_e( 'Canceled', 'air-wp-sync-pro-plus' ); ?>
		<?php else : ?>
			--
		<?php endif; ?>
	</p>

	<?php if ( 'error' === $data['status'] && ! empty( $data['errors'] ) ) : ?>
		<p class="airwpsync-last-error">
			<?php
			foreach ( $data['errors'] as $error ) {
				if ( is_wp_error( $error ) ) {
					echo wp_kses( $error->get_error_message(), array( 'a' => array( 'href' => array() ) ) );
				} elseif ( is_string( $error ) ) {
					echo wp_kses( $error, array( 'a' => array( 'href' => array() ) ) );
				}
			}
			?>
		</p>
	<?php endif; ?>

	<p>
		<?php if ( ! empty( $data['last_updated'] ) ) : ?>
			<?php
			echo esc_html(
				sprintf(
				/* translators: %s: Date */
					__( 'Date: %s', 'air-wp-sync-pro-plus' ),
					Helper::get_formatted_date_time( $data['last_updated'] )
				)
			);
			?>
		<?php else : ?>
			--
		<?php endif; ?>
	</p>

	<?php if ( ! empty( $data['content_ids'] ) ) : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
				/* translators: %s = Number of processed posts */
					__( 'Processed posts: %d', 'air-wp-sync-pro-plus' ),
					count( $data['content_ids'] )
				)
			);
			?>
		</p>
	<?php elseif ( ! empty( $data['count_processed'] ) ) : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
				/* translators: %s = Number of processed posts */
					__( 'Processed posts: %d', 'air-wp-sync-pro-plus' ),
					$data['count_processed']
				)
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $data['latest_log_url'] ) ) : ?>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					'<a href="%s">%s</a>',
					esc_url( $data['latest_log_url'] ),
					__( 'Download the latest log file.', 'air-wp-sync-pro-plus' )
				)
			);
			?>
		</p>
	<?php endif; ?>

	<template x-if="config.scheduled_sync.type === 'cron'">
		<div>
			<p>
				<?php
				echo esc_html(
					sprintf(
					/* translators: %s = Next scheduled sync date */
						__( 'Scheduled Next Sync: %s', 'air-wp-sync-pro-plus' ),
						! empty( $data['next_sync'] ) ? Helper::get_formatted_date_time( $data['next_sync'] ) : '--'
					)
				);
				?>
			</p>
		</div>
	</template>
	<?php
};
