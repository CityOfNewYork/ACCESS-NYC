<?php
/**
 * Display imported post connection details on post edit page in a metabox.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Imported_Content_Connection_Info;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Imported_Content_Importer_Metas;

/**
 * Class Imported_Content_Importer_Metas.
 */
class Metabox_Imported_Content_Connection_Info extends Imported_Content_Connection_Info {

	/**
	 * Factory to create Imported_Content_Importer_Metas.
	 *
	 * @var callable(mixed):Imported_Content_Importer_Metas|false
	 */
	private $imported_content_imported_metas_factory;


	/**
	 * Constructor.
	 *
	 * @param string                                                $imported_content_screen_id Imported content screen id.
	 * @param callable(mixed):Imported_Content_Importer_Metas|false $imported_content_imported_metas_factory Factory to create Imported_Content_Importer_Metas.
	 */
	public function __construct( $imported_content_screen_id, $imported_content_imported_metas_factory ) {
		$this->imported_content_imported_metas_factory = $imported_content_imported_metas_factory;

		parent::__construct( $imported_content_screen_id );
	}

	/**
	 * Build Imported_Content_Importer_Metas from context.
	 *
	 * @return Imported_Content_Importer_Metas|false
	 */
	protected function build_content_imported_metas() {
		global $post;
		$imported_content_imported_metas_factory = $this->imported_content_imported_metas_factory;
		return $imported_content_imported_metas_factory( $post );
	}

	/**
	 * Trigger connection info display.
	 *
	 * @return void
	 */
	protected function trigger_display() {
		add_meta_box(
			'airwpsync-wordpress-content-connection-info-' . $this->get_imported_content_screen_id(),
			__( 'Connection details', 'air-wp-sync-pro-plus' ),
			array( $this, 'display_meta_box' ),
			$this->get_imported_content_screen_id(),
			'side',
			'default'
		);
	}

	/**
	 * Display metabox.
	 *
	 * @param \WP_Post $post Post edited.
	 *
	 * @return void
	 */
	public function display_meta_box( $post ) {
		$this->display( get_edit_post_link( $post->ID ), $post->ID );
	}
}
