<?php
/**
 * Manages admin connections list page.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;

/**
 * Class Admin_Connections_List
 */
class Admin_Connections_List {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'manage_airwpsync-connection_posts_columns', array( $this, 'admin_table_columns' ), 10, 1 );
		add_action( 'manage_airwpsync-connection_posts_custom_column', array( $this, 'admin_table_columns_html' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'connection_row_actions' ), 10, 2 );
	}

	/**
	 * Customizes the admin table columns.
	 *
	 * @param string[] $post_columns An associative array of column headings.
	 */
	public function admin_table_columns( $post_columns ) {
		$columns = array(
			'cb'            => $post_columns['cb'],
			'title'         => $post_columns['title'],
			'modified-date' => __( 'Last Modified On', 'air-wp-sync-pro-plus' ),
			'type'          => __( 'Importer Type', 'air-wp-sync-pro-plus' ),
			'sync-date'     => __( 'Last Synced On', 'air-wp-sync-pro-plus' ),
			'sync-trigger'  => __( 'Trigger', 'air-wp-sync-pro-plus' ),
		);
		return $columns;
	}

	/**
	 * Renders the admin table column HTML
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id The current post ID.
	 * @return void
	 */
	public function admin_table_columns_html( $column_name, $post_id ) {
		$importer = Helper::get_importer_by_id( $post_id );
		if ( ! $importer ) {
			return;
		}
		switch ( $column_name ) {
			case 'type':
				$module_slug = $importer->get_module()->get_slug();
				/* Translators: %s Importer's module name (like "Post", "User") */
				echo esc_html( sprintf( __( '%s Importer', 'air-wp-sync-pro-plus' ), $importer->get_module()->get_name() ) );
				/**
				 * Fires after displaying Importer's module name in admin connection list table.
				 *
				 * @param Abstract_Importer $importer Importer.
				 */
				do_action( 'airwpsync/connections_list_type_column', $importer );
				break;
			case 'sync-trigger':
				if ( 'manual' === $importer->config()->get( 'scheduled_sync.type' ) ) {
					esc_html_e( 'Manual only', 'air-wp-sync-pro-plus' );
				}
				if ( 'cron' === $importer->config()->get( 'scheduled_sync.type' ) ) {
					esc_html_e( 'Recurring', 'air-wp-sync-pro-plus' );
					if ( $importer->config()->get( 'scheduled_sync.recurrence' ) ) {
						$schedules = wp_get_schedules();
						foreach ( $schedules as $slug => $schedule ) {
							if ( $slug === $importer->config()->get( 'scheduled_sync.recurrence' ) ) {
								echo esc_html( ' (' . $schedule['display'] . ')' );
							}
						}
					}
				}
				if ( 'instant' === $importer->config()->get( 'scheduled_sync.type' ) ) {
					esc_html_e( 'Instant via Webhook', 'air-wp-sync-pro-plus' );
				}
				break;
			case 'sync-date':
				$last_updated = $importer->get_last_updated();
				echo esc_html( $last_updated ? Helper::get_formatted_date_time( $last_updated ) : '--' );
				break;
			case 'modified-date':
				$last_modified = $importer->infos()->get( 'modified' );
				echo esc_html( $last_modified ? Helper::get_formatted_date_time( $last_modified ) : '--' );
				break;
		}
	}

	/**
	 * Generates row action links
	 *
	 * @param array    $actions Row actions output for post.
	 * @param \WP_Post $post The current post ID.
	 * @return array
	 */
	public function connection_row_actions( $actions, $post ) {
		if ( 'airwpsync-connection' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );

			if ( isset( $actions['trash'] ) ) {
				$importer = Helper::get_importer_by_id( $post->ID );
				if ( $importer && $importer->config()->get( 'post_type' ) === 'custom' ) {
					$title            = _draft_or_post_title();
					$actions['trash'] = sprintf(
						'<a href="%s" class="submitdelete" aria-label="%s" onclick="return confirm(\'%s\')">%s</a>',
						get_delete_post_link( $post->ID ),
						/* translators: %s: Post title. */
						esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash' ), $title ) ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
						__( 'You have a Custom Post Type declared using this connection. Are you sure to delete it?', 'air-wp-sync-pro-plus' ),
						_x( 'Trash', 'verb' )  // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
					);
				}
			}
		}
		return $actions;
	}
}
