<?php
/**
 * Display imported term connection details on term edit page.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User\Admin;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Imported_Content_Connection_Info;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Imported_Content_Importer_Metas;

/**
 * Class Imported_Term_Connection_Info.
 */
class Imported_Term_Connection_Info extends Imported_Content_Connection_Info {

	/**
	 * Imported term taxonomy.
	 *
	 * @var string
	 */
	private $taxonomy;

	/**
	 * Constructor.
	 *
	 * @param string $taxonomy Imported term taxonomy.
	 */
	public function __construct( $taxonomy ) {
		$this->taxonomy = $taxonomy;
		parent::__construct( 'edit-' . $taxonomy );
	}

	/**
	 * Build Imported_Content_Importer_Metas from context.
	 *
	 * @return Imported_Content_Importer_Metas|false
	 */
	protected function build_content_imported_metas() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$term_id = ! empty( $_REQUEST['tag_ID'] ) ? absint( $_REQUEST['tag_ID'] ) : 0;
		if ( ! $term_id ) {
			return false;
		}
		$term = get_term( $term_id, $this->taxonomy );
		try {
			return new Imported_Content_Importer_Metas(
				get_term_meta( $term->term_id, '_air_wp_sync_importer_id', true ),
				get_term_meta( $term->term_id, '_air_wp_sync_record_id', true ),
				get_term_meta( $term->term_id, '_air_wp_sync_updated_at', true ),
				function ( $term_id ) {
					return delete_term_meta( $term_id, '_air_wp_sync_importer_id' )
							&& delete_term_meta( $term_id, '_air_wp_sync_record_id' )
							&& delete_term_meta( $term_id, '_air_wp_sync_updated_at' );
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
		add_action( "{$this->taxonomy}_edit_form_fields", array( $this, 'display_term_connection_info' ), 10, 1 );
	}

	/**
	 * Display connection details on user edit screen.
	 *
	 * @param \WP_Term $tag Current taxonomy term object.
	 */
	public function display_term_connection_info( $tag ) {
		?><tr class="form-field"><th scope="row"><label for="airwpsync-imported-term-connection-details">
			<?php
				esc_html_e( 'Connection details', 'air-wp-sync-pro-plus' );
			?>
			</label></th><td>
			<?php
				$this->display( get_edit_term_link( $tag->term_id ), $tag->term_id )
			?>
			</td></tr>
		<?php
	}
}
