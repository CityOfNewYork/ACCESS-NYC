<?php

class WPML_TM_Post_Edit_TM_Editor_Select implements IWPML_Action {

	const SCRIPT_HANDLE = 'wpml-post-edit-tm-mode';
	const NONCE_ACTION  = 'wpml-tm-editor-mode';

	/** @var SitePress $sitepress */
	private $sitepress;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wpml_before_post_edit_translations_table', array( $this, 'add_ui' ) );
		add_action( 'wpml_before_post_edit_translations_summary', array( $this, 'add_ui' ) );
		add_action( 'wp_ajax_wpml-tm-editor-mode', array( $this, 'save_mode' ) );
	}

	public function enqueue_scripts() {
		wp_register_script(
			self::SCRIPT_HANDLE,
			WPML_TM_URL . '/dist/js/postEditTranslationEditor/app.js',
			array( 'jquery-ui-dialog' ),
			WPML_TM_VERSION,
			true
		);

		wp_enqueue_script( self::SCRIPT_HANDLE );

		wp_enqueue_style( 'otgs-switcher' );
	}

	public function add_ui( WP_Post $post ) {
		/**
		 * Filters the TM editor select control.
		 *
		 * @since 4.5.3
		 * @internal
		 *
		 * @param bool  $use_tm_editor Use TM editor.
		 */
		if ( ! apply_filters( 'wpml_tm_post_edit_tm_editor_selector_display', true ) ) {
			return;
		}

		$this->prevent_displaying_ui_twice();

		$is_source = $this->sitepress->is_original_content_filter( false, $post->ID, 'post_' . $post->post_type );

		if ( $is_source ) {
			$this->add_checkbox( $post );
			$this->add_dialog( $post );
		}
	}

	private function prevent_displaying_ui_twice() {
		remove_action( 'wpml_before_post_edit_translations_table', array( $this, 'add_ui' ) );
		remove_action( 'wpml_before_post_edit_translations_summary', array( $this, 'add_ui' ) );
	}

	private function add_checkbox( WP_Post $post ) {
		$wpml_tm_editor = esc_html__( "Use WPML's Translation Editor", 'wpml-translation-management' );
		$checked        = WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $this->sitepress, $post->ID ) ? 'checked' : '';

		?>
		<p class="otgs-toggle-group">
			<input type="checkbox" class="js-toggle-tm-editor otgs-switcher-input" id="wpml_tm_toggle_tm_editor" <?php echo $checked; ?>>
			<label for="wpml_tm_toggle_tm_editor" class="otgs-switcher wpml-theme" data-on="ON" data-off="OFF"><?php echo $wpml_tm_editor; ?></label>
		</p>
		<?php
	}

	private function add_dialog( WP_Post $post ) {
		global $wp_post_types;

		$wpml_tm_editor = esc_html__( "Use WPML's Translation Editor to translate:", 'wpml-translation-management' );
		$wp_editor      = esc_html__( "Use the WordPress Editor to translate:", 'wpml-translation-management' );
		$cancel_text    = esc_html__( 'Cancel', 'wpml-translation-management' );
		$apply_text     = esc_html__( 'Apply', 'wpml-translation-management' );

		$post_label_singular = sprintf(
		/* translators: %s: Post name singular */
			esc_html__( 'This %s', 'wpml-translation-management' ),
			$wp_post_types[ $post->post_type ]->labels->singular_name
		);
		$post_label_plural   = sprintf(
		/* translators: %s: Post name plural */
			esc_html__( 'All %s', 'wpml-translation-management' ),
			$wp_post_types[ $post->post_type ]->labels->name
		);
		$all_site_content    = esc_html__( "All the site's content", 'wpml-translation-management' );
		$nonce               = wp_create_nonce( self::NONCE_ACTION );

		?>
		<div
				id="js-tm-editor-dialog"
				data-tm-editor="<?php echo $wpml_tm_editor; ?>"
				data-wp-editor="<?php echo $wp_editor; ?>"
				data-cancel-text="<?php echo $cancel_text; ?>"
				data-apply-text="<?php echo $apply_text; ?>"
				data-nonce="<?php echo $nonce; ?>"
				style="display: none"
		>
			<label for="tm-editor-this">
				<input type="radio" id="tm-editor-this" name="tm-editor-mode" value="this_post"
					   checked/><?php echo $post_label_singular; ?>
			</label>
			<br/>

			<label for="tm-editor-all">
				<input type="radio" id="tm-editor-all" name="tm-editor-mode"
					   value="all_posts_of_type"/><?php echo $post_label_plural; ?>
			</label>
			<br/>

			<label for="tm-editor-global">
				<input type="radio" id="tm-editor-global" name="tm-editor-mode"
					   value="global"/><?php echo $all_site_content; ?>
			</label>

		</div>
		<?php
	}

	public function save_mode() {
		if ( wp_verify_nonce( $_POST['nonce'], self::NONCE_ACTION ) ) {
			$tm_settings = $this->sitepress->get_setting( 'translation-management' );

			$use_native_editor = 'false' === $_POST['useTMEditor'];
			$post_id           = filter_var( $_POST['postId'], FILTER_SANITIZE_NUMBER_INT );

			switch ( $_POST['mode'] ) {
				case 'global':
					$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_GLOBAL_USE_NATIVE ]        = $use_native_editor;
					$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ] = array();
					$this->sitepress->set_setting( 'translation-management', $tm_settings, true );
					WPML_TM_Post_Edit_TM_Editor_Mode::delete_all_posts_option();
					break;

				case 'all_posts_of_type':
					$post_type                                                                                      = get_post_type( $post_id );
					$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ][ $post_type ] = $use_native_editor;
					$this->sitepress->set_setting( 'translation-management', $tm_settings, true );
					WPML_TM_Post_Edit_TM_Editor_Mode::delete_all_posts_option( $post_type );
					break;

				case 'this_post':
					update_post_meta(
						$post_id,
						WPML_TM_Post_Edit_TM_Editor_Mode::POST_META_KEY_USE_NATIVE,
						$use_native_editor ? 'yes' : 'no'
					);
					break;
			}
		}
		wp_send_json_success();
	}

}
