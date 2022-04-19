<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Admin;
use GatherContent\Importer\General;
use GatherContent\Importer\API;
use GatherContent\Importer\Admin\Enqueue;
use GatherContent\Importer\Admin\Mapping_Wizard;

/**
 * Handles the UI for the bulk/quick-editing on post-listing page.
 *
 * @since 3.0.0
 */
class Bulk extends Post_Base {

	/**
	 * The current listing page's post-type object.
	 *
	 * @var object
	 */
	protected $post_type_object;

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	protected function script_id() {
		return 'gathercontent-general';
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		$this->post_types = $this->wizard->mappings->get_mapping_post_types();

		global $pagenow;
		if (
			$pagenow
			&& ! empty( $this->post_types )
			&& 'edit.php' === $pagenow
			) {
			add_action( 'admin_enqueue_scripts', array( $this, 'ui' ) );
		}

		if ( $this->doing_ajax && ! empty( $this->post_types ) ) {
			foreach ( $this->post_types as $post_type => $mapping_ids ) {
				add_filter( "manage_{$post_type}_posts_columns", array( $this, 'register_column_headers' ), 8 );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'column_display' ), 10, 2 );
			}
		}

		// Handle quick-edit/bulk-edit ajax-post-saving.
		add_action( 'save_post', array( $this, 'set_gc_status' ), 10, 2 );
	}

	/**
	 * The Bulk Edit page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void|bool
	 */
	public function ui_page() {
		$screen = get_current_screen();

		if (
			'edit' !== $screen->base
			|| ! $screen->post_type
		) {
			return false;
		}

		if ( ! isset( $this->post_types[ $screen->post_type ] ) ) {
			return false;
		}

		wp_enqueue_style( 'media-views' );

		$this->enqueue->admin_enqueue_style();
		$this->enqueue->admin_enqueue_script();

		$this->hook_columns( $screen->post_type );
	}

	/**
	 * Hooks the column callbacks for the current screen's post-type.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $post_type Current screen's post-type.
	 *
	 * @return void
	 */
	public function hook_columns( $post_type ) {
		add_filter( "manage_{$post_type}_posts_columns", array( $this, 'register_column_headers' ), 8 );
		add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'column_display' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_box' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this, 'bulk_edit_box' ), 10, 2 );

		$this->post_type_object = get_post_type_object( $post_type );
	}

	/**
	 * Register the GC column header.
	 *
	 * @since 3.0.0
	 *
	 * @param  array $columns Array of column header names.
	 *
	 * @return array
	 */
	public function register_column_headers( $columns ) {
		$columns['gathercontent'] = '<div title="' . __( 'GatherContent Item Status', 'gathercontent-importer' ) . '" class="gc-column-header"><span class="gc-logo-column"><img src="' . GATHERCONTENT_URL . 'images/logo.svg" alt="GatherContent" /></span>' . _x( 'Status', 'GatherContent Item Status', 'gathercontent-importer' ) . '</div>';

		return $columns;
	}

	/**
	 * The GC field column display output.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $column_name Column name.
	 * @param  int    $post_id     Post ID.
	 *
	 * @return void
	 */
	public function column_display( $column_name, $post_id ) {
		if ( 'gathercontent' !== $column_name ) {
			return;
		}
		global $post;

		$js_post = \GatherContent\Importer\prepare_post_for_js( $post );

		if ( $this->doing_ajax ) {
			return $this->ajax_view( $post_id, $js_post['item'], $js_post['mapping'] );
		}

		printf(
			'<span class="gc-status-column" data-id="%d" data-item="%d" data-mapping="%d">&mdash;</span>',
			absint( $post_id ),
			absint( $js_post['item'] ),
			absint( $js_post['mapping'] )
		);

		// Save post object for backbone data.
		$this->posts[] = $js_post;
	}

	/**
	 * Handles the column view if it's being ajax-loaded.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $post_id    Post ID.
	 * @param  int $item_id    Item id.
	 * @param  int $mapping_id Mapping id.
	 *
	 * @return void
	 */
	protected function ajax_view( $post_id, $item_id, $mapping_id ) {
		$status_name = $status_color = $status_id = '';

		if ( $item_id ) {

			$item = $this->api->uncached()->get_item( $item_id );

			if ( isset( $item->status->data ) ) {
				$status_id = $item->status->data->id;
				$status_name = $item->status->data->name;
				$status_color = $item->status->data->color;
			}
		}

		$this->view( 'gc-post-column-row', array(
			'post_id'      => $post_id,
			'item_id'      => $item_id,
			'mapping_id'   => $mapping_id,
			'status_id'    => $status_id,
			'status_name'  => $status_name,
			'status_color' => $status_color,
		) );
	}

	/**
	 * The GC field quick-edit display output.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $column_name Column name.
	 * @param  string $post_type   Post type.
	 *
	 * @return void
	 */
	public function quick_edit_box( $column_name, $post_type ) {
		if ( 'gathercontent' !== $column_name ) {
			return;
		}

		$this->view( 'quick-edit-field', compact( 'column_name' ) );
	}

	/**
	 * The GC field bulk-edit display output.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $column_name Column name.
	 * @param  string $post_type   Post type.
	 *
	 * @return void
	 */
	public function bulk_edit_box( $column_name, $post_type ) {
		if ( 'gathercontent' !== $column_name ) {
			return;
		}

		$this->view( 'bulk-edit-field', array(
			'refresh_link' => \GatherContent\Importer\refresh_connection_link(),
		) );
	}

	/**
	 * Sets the GatherContent status if being requested via quick-edit box.
	 *
	 * @since 3.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param object $post    Post object.
	 */
	public function set_gc_status( $post_id, $post ) {
		if (
			wp_is_post_autosave( $post )
			|| wp_is_post_revision( $post )
			|| ! $this->_post_val( 'gc-edit-nonce' )
			|| ! wp_verify_nonce( $this->_post_val( 'gc-edit-nonce' ), GATHERCONTENT_SLUG )
			|| ! ( $status_id = $this->_post_val( 'gc_status' ) )
			|| ! ( $item_id = absint( \GatherContent\Importer\get_post_item_id( $post_id ) ) )
			|| ! ( $mapping_id = absint( \GatherContent\Importer\get_post_mapping_id( $post_id ) ) )
			|| ! ( $item = $this->api->get_item( $item_id ) )
			|| ( isset( $item->status->data->id ) && absint( $status_id ) === absint( $item->status->data->id ) )
		) {
			return;
		}

		$this->api->set_item_status( $item_id, $status_id );
	}

	/**
	 * Gets the underscore templates array.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	protected function get_underscore_templates() {
		if ( empty( $this->posts ) ) {
			return array();
		}

		return array(
			'tmpl-gc-table-search'    => array(),
			'tmpl-gc-table-nav'       => array(),
			'tmpl-gc-post-column-row' => array(),
			'tmpl-gc-status-select2'  => array(),
			'tmpl-gc-select2-item'    => array(),
			'tmpl-gc-modal-window'    => array(
				'nav' => array(
					$this->wizard->parent_url            => __( 'Settings', 'gathercontent-import' ),
					$this->wizard->mappings->listing_url => $this->wizard->mappings->args->label,
					$this->wizard->url                   => $this->wizard->mappings->args->labels->new_item,
				),
				'headers' => array(
					'status'      => __( 'Status', 'gathercontent-import' ),
					'itemName'    => __( 'Item', 'gathercontent-import' ),
					'updated_at'  => __( 'Updated', 'gathercontent-import' ),
					'mappingName' => __( 'Template Mapping', 'gathercontent-import' ),
					'post_title'  => __( 'WordPress Title', 'gathercontent-import' ),
				),
			),
			'tmpl-gc-item' => array(
				'url' => General::get_instance()->admin->platform_url(),
			),
			'tmpl-gc-mapping-metabox' => array(
				'message' => esc_html__( 'Fetching GatherContent Accounts', 'gathercontent-importer' ),
			),
		);
	}

	/**
	 * Get the localizable data array.
	 *
	 * @since  3.0.0
	 *
	 * @return array Array of localizable data
	 */
	protected function get_localize_data() {
		if ( empty( $this->posts ) ) {
			return array();
		}

		$plural_label = $this->post_type_object->labels->name;

		$data = parent::get_localize_data();

		$data['_posts']      = $this->posts;
		$data['_modal_btns'] = array(
			array(
				'label'   => __( 'Assign Template Mapping', 'gathercontent-importer' ),
				'id'      => 'assign-mapping',
				'primary' => false,
			),
			array(
				'label'   => __( 'Push Items', 'gathercontent-importer' ),
				'id'      => 'push',
				'primary' => false,
			),
			array(
				'label'   => __( 'Pull Items', 'gathercontent-importer' ),
				'id'      => 'pull',
				'primary' => true,
			),
		);

		$data['_sure'] = array(
			'push' => sprintf( __( 'Are you sure you want to push these %s to GatherContent? Any unsaved changes in GatherContent will be overwritten.', 'gathercontent-importer' ), $plural_label ),
			'pull'  => sprintf( __( 'Are you sure you want to pull these %s from GatherContent? Any local changes will be overwritten.', 'gathercontent-importer' ), $plural_label ),
		);

		$data['_text'] = array(
			'no_items' => esc_html__( 'No items found.', 'gathercontent-import' ),
		);

		return $data;
	}

}
