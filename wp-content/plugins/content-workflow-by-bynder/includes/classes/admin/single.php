<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Admin;

use GatherContent\Importer\Admin\Mapping_Wizard;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\General;
use GatherContent\Importer\API;
use GatherContent\Importer\Admin\Enqueue;

/**
 * Handles the UI for the metabox on the post-edit page.
 *
 * @since 3.0.0
 */
class Single extends Post_Base {

	/**
	 * This JS post array.
	 *
	 * @var array
	 */
	protected $post = array();

	/**
	 * This post's post-type label.
	 *
	 * @var string
	 */
	protected $post_type_label = '';

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @return string
	 * @since  3.0.0
	 *
	 */
	protected function script_id() {
		return 'gathercontent-single';
	}

	/**
	 * Initiate admin hooks
	 *
	 * @return void
	 * @since  3.0.0
	 *
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
			&& 'post.php' === $pagenow
		) {
			add_action( 'admin_enqueue_scripts', array( $this, 'ui' ) );
		}
	}

	/**
	 * The Bulk Edit page UI callback.
	 *
	 * @return void|bool
	 * @since  3.0.0
	 *
	 */
	public function ui_page() {
		$screen = get_current_screen();

		if ( 'post' !== $screen->base || ! $screen->post_type ) {
			return false;
		}

		// Do not show GC metabox if there is no mapping for this post-type, or if this is a new post.
		if ( ! isset( $this->post_types[ $screen->post_type ] ) || ! $this->_get_val( 'post' ) ) {
			return false;
		}

		$this->enqueue->admin_enqueue_style();
		$this->enqueue->admin_enqueue_script();
		add_meta_box( 'gc-manage', 'Content Workflow <span class="dashicons dashicons-randomize"></span>', array(
			$this,
			'meta_box'
		), $screen, 'side', 'high' );
	}

	/**
	 * Metabox callback for outputting the metabox contents.
	 *
	 * @param object $post Post object.
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function meta_box( $post ) {
		$object                = get_post_type_object( $post->post_type );
		$this->post_type_label = isset( $object->labels->singular_name ) ? $object->labels->singular_name : $object->name;

		$this->post = \GatherContent\Importer\prepare_post_for_js( $post );

		$this->view(
			'metabox',
			array(
				'post_id'    => $this->post['id'],
				'item_id'    => $this->post['item'],
				'mapping_id' => $this->post['mapping'],
				'label'      => $this->post_type_label,
			)
		);
	}

	/**
	 * Gets the underscore templates array.
	 *
	 * @return array
	 * @since  3.0.0
	 *
	 */
	protected function get_underscore_templates() {
		return array(
			'tmpl-gc-metabox'          => array(
				'url'   => General::get_instance()->admin->platform_url(),
				'label' => $this->post_type_label,
			),
			'tmpl-gc-metabox-statuses' => array(),
			'tmpl-gc-mapping-metabox'  => array(
				'message' => sprintf( esc_html__( 'This %s does not have an associated item or Template Mapping. (Please make sure that you have added a mapping in Content Workflow > New Mapping)', 'content-workflow-by-bynder' ), $this->post_type_label ),
			),
			'tmpl-gc-status-select2'   => array(),
			'tmpl-gc-select2-item'     => array(),
		);
	}

	/**
	 * Get the localizable data array.
	 *
	 * @return array Array of localizable data
	 * @since  3.0.0
	 *
	 */
	protected function get_localize_data() {
		$data = parent::get_localize_data();

		$data['_post'] = $this->post;
		$data['_sure'] = array(
			'push_no_item' => sprintf( __( 'Push this %s to Content Workflow?', 'content-workflow-by-bynder' ), $this->post_type_label ),
			'push'         => sprintf( __( 'Are you sure you want to push this %s to GatherContent? Any unsaved changes in Content Workflow will be overwritten.', 'content-workflow-by-bynder' ), $this->post_type_label ),
			'pull'         => sprintf( __( 'Are you sure you want to pull this %s from Content Workflow? Any local changes will be overwritten.', 'content-workflow-by-bynder' ), $this->post_type_label ),
			'disconnect'   => sprintf( __( 'Are you sure you want to disconnect this %s from Content Workflow?', 'content-workflow-by-bynder' ), $this->post_type_label ),
		);

		return $data;
	}

}
