<?php

class WPML_Meta_Boxes_Post_Edit_Ajax implements IWPML_Action {

	const ACTION_GET_META_BOXES = 'wpml_get_meta_boxes_html';
	const ACTION_GET_ADMIN_LS = 'wpml_get_admin_ls_links';
	const ACTION_DUPLICATE      = 'make_duplicates';

	private $meta_boxes_post_edit_html;
	private $translation_management;

	private $admin_language_switcher;

	public function __construct(
		WPML_Meta_Boxes_Post_Edit_HTML $meta_boxes_post_edit_html,
		TranslationManagement $iclTranslationManagement,
		WPML_Admin_Language_Switcher $admin_language_switcher
	) {
		$this->translation_management = $iclTranslationManagement;
		$this->meta_boxes_post_edit_html = $meta_boxes_post_edit_html;
		$this->admin_language_switcher = $admin_language_switcher;
	}

	public function add_hooks() {
		add_action( 'wp_ajax_' . self::ACTION_GET_META_BOXES, array( $this, 'render_meta_boxes_html' ) );
		add_action( 'wp_ajax_' . self::ACTION_GET_ADMIN_LS, [ $this, 'get_admin_ls_links' ] );
		add_action( 'wp_ajax_' . self::ACTION_DUPLICATE, array( $this, 'duplicate_post' ) );
		add_filter( 'wpml_post_edit_can_translate', array( $this, 'force_post_edit_when_refreshing_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {
		if (
			in_array( $hook, [ 'post.php', 'post-new.php', 'edit.php' ], true ) ||
			apply_filters( 'wpml_enable_language_meta_box', false )
		) {
			wp_enqueue_script( 'wpml-meta-box', ICL_PLUGIN_URL . '/dist/js/wpml-meta-box/wpml-meta-box.js', [], ICL_SITEPRESS_VERSION, true );
		}
	}

	public function render_meta_boxes_html() {
		if ( $this->is_valid_request( self::ACTION_GET_META_BOXES ) ) {
			$post_id = (int) $_POST['post_id'];
			$this->meta_boxes_post_edit_html->render_languages( get_post( $post_id ) );
			wp_die();
		}
	}

	public function get_admin_ls_links() {
		if ( $this->is_valid_request( self::ACTION_GET_ADMIN_LS ) ) {
			$links = $this->admin_language_switcher->get_languages_links();
			wp_send_json_success( $links );
		}
	}

	/**
	 * @param bool $is_edit_page
	 *
	 * @return bool
	 */
	public function force_post_edit_when_refreshing_meta_boxes( $is_edit_page ) {
		return isset( $_POST['action'] ) && self::ACTION_GET_META_BOXES === $_POST['action'] ? true : $is_edit_page;
	}

	public function duplicate_post() {
		if ( $this->is_valid_request( self::ACTION_DUPLICATE ) ) {
			$post_id          = (int) $_POST['post_id'];
			$mdata['iclpost'] = array( $post_id );

			$langs            = explode( ',', $_POST['langs'] );
			foreach ( $langs as $lang ) {
				$mdata['duplicate_to'][ $lang ] = 1;
			}

			$this->translation_management->make_duplicates( $mdata );
			do_action( 'wpml_new_duplicated_terms', (array) $mdata['iclpost'], false );
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * @param string $action
	 * @return bool
	 */
	private function is_valid_request( $action ) {
		$action = $action ? $action : self::ACTION_GET_META_BOXES;
		return isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], $action );
	}
}
