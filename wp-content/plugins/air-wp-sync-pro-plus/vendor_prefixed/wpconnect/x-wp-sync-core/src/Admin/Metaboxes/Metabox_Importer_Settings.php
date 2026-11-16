<?php
/**
 * Display modules settings.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Util\Helper;

/**
 * Class Metabox_Importer_Settings
 */
class Metabox_Importer_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		add_meta_box(
			'airwpsync-post-settings',
			__( 'Import As...', 'air-wp-sync-pro-plus' ),
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
	public function display( $post ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$modules = Helper::get_modules();
		$view    = include_once __DIR__ . '/views/metabox-importer-settings.php';
		$view( $modules, $post );
	}
}
