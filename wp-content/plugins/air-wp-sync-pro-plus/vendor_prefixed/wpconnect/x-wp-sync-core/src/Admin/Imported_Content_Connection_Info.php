<?php
/**
 * Abstract class to manage imported content connection info display.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Imported_Content_Importer_Metas;

/**
 * Class Imported_Content_Importer_Metas.
 */
abstract class Imported_Content_Connection_Info {

	/**
	 * Imported content screen id.
	 *
	 * @var string
	 */
	private $imported_content_screen_id;


	/**
	 * Imported_Content_Importer_Metas instance.
	 *
	 * @var Imported_Content_Importer_Metas|false
	 */
	private $content_imported_metas;

	/**
	 * Constructor.
	 *
	 * @param string $imported_content_screen_id Imported content screen id.
	 */
	public function __construct( $imported_content_screen_id ) {
		$this->imported_content_screen_id = $imported_content_screen_id;

		add_action( 'admin_head', array( $this, 'init' ) );
	}

	/**
	 * Init
	 */
	public function init() {
		if ( get_current_screen()->id !== $this->imported_content_screen_id ) {
			return;
		}

		if ( ! current_user_can( apply_filters( 'airwpsync/manage_options_capability', 'manage_options' ) ) ) {
			return;
		}

		$this->content_imported_metas = $this->build_content_imported_metas();
		if ( ! $this->content_imported_metas ) {
			return;
		}

		$disconnecting = isset( $_GET['air_wp_sync_action'] ) && sanitize_text_field( wp_unslash( $_GET['air_wp_sync_action'] ) ) === 'air_wp_sync_disconnect_content_' . $this->imported_content_screen_id;
		if ( $disconnecting && isset( $_GET['air_wp_sync_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['air_wp_sync_wpnonce'] ) ), 'air_wp_sync_disconnect_content' ) && isset( $_GET['air_wp_sync_content_id'] ) ) {
			$content_id           = sanitize_text_field( wp_unslash( $_GET['air_wp_sync_content_id'] ) );
			$disconnecting_result = $this->content_imported_metas->get_disconnect()( $content_id );
			if ( $disconnecting_result ) {
				add_action( 'admin_notices', array( $this, 'success_notice' ) );
				return;
			} else {
				add_action( 'admin_notices', array( $this, 'failed_notice' ) );
			}
		}

		$this->trigger_display();
	}

	/**
	 * Display connection details.
	 *
	 * @param string $edit_display_page_link The current edit page link.
	 * @param string $content_id The content id.
	 */
	public function display( $edit_display_page_link, $content_id ) {
		$connection      = get_post( $this->content_imported_metas->get_importer_id() );
		$connection_link = apply_filters( 'airwpsync/edit_connection_link', get_edit_post_link( $connection ), $connection );
		$disconnect_url  = add_query_arg(
			array(
				'air_wp_sync_action'     => 'air_wp_sync_disconnect_content_' . $this->imported_content_screen_id,
				'air_wp_sync_wpnonce'    => wp_create_nonce( 'air_wp_sync_disconnect_content' ),
				'air_wp_sync_content_id' => $content_id,
			),
			$edit_display_page_link
		);

		$view = include __DIR__ . '/Metaboxes/views/metabox-side/imported-content-connection-info.php';
		$view( $this->content_imported_metas, $connection, $connection_link, $disconnect_url );
	}

	/**
	 * Success notice.
	 *
	 * @return void
	 */
	public function success_notice() {
		?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Content successfully disconnected from the connection.', 'air-wp-sync-pro-plus' ); ?></p></div>
		<?php
	}

	/**
	 * Failed notice.
	 *
	 * @return void
	 */
	public function failed_notice() {
		?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'An error has occurred, we could not disconnect the content from the connection.', 'air-wp-sync-pro-plus' ); ?></p></div>
		<?php
	}

	/**
	 * Returns imported content screen id.
	 *
	 * @return string
	 */
	protected function get_imported_content_screen_id(): string {
		return $this->imported_content_screen_id;
	}

	/**
	 * Build Imported_Content_Importer_Metas from context.
	 *
	 * @return Imported_Content_Importer_Metas|false
	 */
	abstract protected function build_content_imported_metas();

	/**
	 * Trigger connection info display.
	 *
	 * @return void
	 */
	abstract protected function trigger_display();
}
