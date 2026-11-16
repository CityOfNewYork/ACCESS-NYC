<?php
/**
 * Manage the sync strategy options: manual, recurring...
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Class Metabox_Sync_Settings
 */
class Metabox_Sync_Settings {
	/**
	 * Plugin's specific strings.
	 *
	 * @var Plugin_Strings
	 */
	private $plugin_strings;

	/**
	 * Constructor
	 *
	 * @param Plugin_Strings $plugin_strings Plugin’s specific strings.
	 */
	public function __construct( $plugin_strings ) {
		$this->plugin_strings = $plugin_strings;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		add_meta_box(
			'airwpsync-sync-settings',
			__( 'Sync Settings', 'air-wp-sync-pro-plus' ),
			array( $this, 'display' ),
			'airwpsync-connection',
			'normal',
			'high'
		);
	}

	/**
	 * Output metabox HTML
	 *
	 * @param \WP_Post $post The connection.
	 */
	public function display( $post ) {
		$importer          = Helper::get_importer_by_id( $post->ID );
		$webhook_url       = $importer ? get_rest_url( null, 'airwpsync/v1/import/' . $importer->infos()->get( 'hash' ) ) : null;
		$sync_strategies   = $this->get_sync_strategies();
		$schedules         = $this->get_schedules();
		$other_connections = array();
		$importers         = Helper::get_importers();
		foreach ( $importers as $importer ) {
			if ( $importer->infos()->get( 'id' ) === $post->ID ) {
				continue;
			}
			$other_connections[ $importer->infos()->get( 'id' ) ] = $importer->infos()->get( 'title' );
		}
		$view = include_once __DIR__ . '/views/metabox-sync.php';
		$view( $sync_strategies, $schedules, $webhook_url, $this->plugin_strings, $other_connections );
	}

	/**
	 * Get sync strategies
	 */
	protected function get_sync_strategies() {
		return array(
			'add_update_delete' => __( 'Add, Update & Delete', 'air-wp-sync-pro-plus' ),
			'add_update'        => __( 'Add & Update', 'air-wp-sync-pro-plus' ),
			'add'               => __( 'Add', 'air-wp-sync-pro-plus' ),
			'delete'            => __( 'Delete', 'air-wp-sync-pro-plus' ),
		);
	}

	/**
	 * Get schedules
	 */
	protected function get_schedules() {
		$schedules = array();

		$wp_schedules = array_filter(
			wp_get_schedules(),
			function ( $schedule ) {
				return isset( $schedule['interval'] ) && $schedule['interval'] >= 1800;
			}
		);

		foreach ( $wp_schedules as $key => $schedule ) {
			$schedules[ $schedule['interval'] ] = array(
				'value'   => $key,
				'label'   => $schedule['display'],
				'enabled' => true,
			);
		}
		ksort( $schedules );
		return array_reverse( $schedules );
	}
}
