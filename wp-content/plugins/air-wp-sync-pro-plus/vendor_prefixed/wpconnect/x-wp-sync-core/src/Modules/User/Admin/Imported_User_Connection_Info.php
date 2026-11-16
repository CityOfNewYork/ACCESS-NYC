<?php
/**
 * Display imported user connection details on user edit page.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Imported_Content_Connection_Info;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Imported_Content_Importer_Metas;

/**
 * Class Imported_User_Connection_Info.
 */
class Imported_User_Connection_Info extends Imported_Content_Connection_Info {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'user-edit' );
	}

	/**
	 * Build Imported_Content_Importer_Metas from context.
	 *
	 * @return Imported_Content_Importer_Metas|false
	 */
	protected function build_content_imported_metas() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id = ! empty( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;
		if ( ! $user_id ) {
			return false;
		}
		$user = get_user( $user_id );
		try {
			return new Imported_Content_Importer_Metas(
				get_user_meta( $user->ID, '_air_wp_sync_importer_id', true ),
				get_user_meta( $user->ID, '_air_wp_sync_record_id', true ),
				get_user_meta( $user->ID, '_air_wp_sync_updated_at', true ),
				function ( $user_id ) {
					return delete_user_meta( $user_id, '_air_wp_sync_importer_id' )
							&& delete_user_meta( $user_id, '_air_wp_sync_record_id' )
							&& delete_user_meta( $user_id, '_air_wp_sync_updated_at' );
				}
			);
		} catch ( \Throwable $exception ) {
			// Certainly not a content imported from a connection.
			return false;
		}
	}

	/**
	 * Trigger connection info display.
	 *
	 * @return void
	 */
	protected function trigger_display() {
		add_action( 'edit_user_profile', array( $this, 'display_user_connection_info' ) );
	}

	/**
	 * Display connection details on user edit screen.
	 *
	 * @param \WP_User $user The edited user.
	 */
	public function display_user_connection_info( $user ) {
		printf( '<h2>%s</h2>', esc_html__( 'Connection details', 'air-wp-sync-pro-plus' ) );
		$this->display( get_edit_user_link( $user->ID ), $user->ID );
	}
}
