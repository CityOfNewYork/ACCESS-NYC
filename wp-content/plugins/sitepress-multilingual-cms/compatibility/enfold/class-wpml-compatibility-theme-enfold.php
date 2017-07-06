<?php

/**
 * Class WPML_Compatibility_Theme_Enfold
 */
class WPML_Compatibility_Theme_Enfold {
	/** @var TranslationManagement */
	private $translation_management;

	/**
	 * @param TranslationManagement $translation_management
	 */
	public function __construct( TranslationManagement $translation_management ) {
		$this->translation_management = $translation_management;
	}


	public function init_hooks() {
		add_action( 'wp_insert_post', array( $this, 'wp_insert_post_action' ), 10, 2 );
	}

	/**
	 * Enfold's page builder is keeping the content in the custom field "_aviaLayoutBuilderCleanData" (maybe to prevent the content
	 * from being altered by another plugin). The standard post content will be displayed only if the field
	 * "_aviaLayoutBuilder_active" or "_avia_builder_shortcode_tree" does not exist.
	 *
	 * "_aviaLayoutBuilder_active" and "_avia_builder_shortcode_tree" fields should be set to "copy" in wpml-config.xml.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public function wp_insert_post_action( $post_id, $post ) {
		if ( ! $this->is_tm_editor_used() ) {
			return;
		}

		$is_original = apply_filters( 'wpml_is_original_content', false, $post_id, 'post_' . $post->post_type );

		if ( ! $is_original ) {
			$page_builder_active         = get_post_meta( $post_id, '_aviaLayoutBuilder_active', true );
			$page_builder_shortcode_tree = get_post_meta( $post_id, '_avia_builder_shortcode_tree', true );

			if ( $page_builder_active && $page_builder_shortcode_tree ) {
				update_post_meta( $post_id, '_aviaLayoutBuilderCleanData', $post->post_content );
			}
		}
	}

	/**
	 * @return bool
	 */
	private function is_tm_editor_used() {
		$doc_translation_method = isset( $this->translation_management->settings['doc_translation_method'] ) ?
			(int) $this->translation_management->settings['doc_translation_method'] :
			ICL_TM_TMETHOD_MANUAL;

		return ICL_TM_TMETHOD_EDITOR === $doc_translation_method;
	}
}
