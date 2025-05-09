<?php

use WPML\PB\Gutenberg\StringsInBlock\Base;

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
		add_action( 'wp_insert_post', [ $this, 'wp_insert_post_action' ], 10, 2 );
		add_filter( 'wpml_pb_before_replace_string_with_translation', [ $this, 'replace_single_quotes' ], 10, 2 );
		add_filter( 'wpml_pb_shortcode_content_for_translation', [ $this, 'get_content_from_custom_field' ], 10, 2 );
		add_action( 'icl_make_duplicate', [ $this, 'sync_duplicate' ], 10, 4 );
		add_filter( 'wpml_pb_is_post_built_with_shortcodes', [ $this, 'isPostBuiltWithShortcodes' ], 10, 2 );
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
		if ( $this->is_using_standard_wp_editor() || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$is_original = apply_filters( 'wpml_is_original_content', false, $post_id, 'post_' . $post->post_type );

		if ( ! $is_original ) {
			if ( $this->is_active( $post_id ) && $this->is_post_using_tm_editor( $post_id ) ) {
				update_post_meta( $post_id, '_aviaLayoutBuilderCleanData', $post->post_content );
			}
		}
	}

	/**
	 * @param string $content
	 * @param int    $post_id
	 *
	 * @return string
	 */
	public function get_content_from_custom_field( $content, $post_id ) {

		if ( $this->is_active( $post_id ) ) {
			$content = str_replace( "\r\n", "\n", get_post_meta( $post_id, '_aviaLayoutBuilderCleanData', true ) );
		}

		if ( 'VISUAL' !== Base::get_string_type( $content ) ) {
			$content = html_entity_decode( $content );
		}

		return $content;
	}

	/**
	 * @param int    $master_post_id
	 * @param string $lang
	 * @param array  $post_array
	 * @param int    $id
	 */
	function sync_duplicate( $master_post_id, $lang, $post_array, $id ) {
		if ( $this->is_active( $master_post_id ) ) {
			$data = get_post_meta( $master_post_id, '_aviaLayoutBuilderCleanData', true );
			update_post_meta( $id, '_aviaLayoutBuilderCleanData', $data );
		}
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function is_active( $post_id ) {
		$page_builder_active         = get_post_meta( $post_id, '_aviaLayoutBuilder_active', true );
		$page_builder_shortcode_tree = get_post_meta( $post_id, '_avia_builder_shortcode_tree', true );

		return $page_builder_active && $page_builder_shortcode_tree !== '';
	}

	/**
	 * @return bool
	 */
	private function is_using_standard_wp_editor() {
		$doc_translation_method = isset( $this->translation_management->settings['doc_translation_method'] ) ?
		$this->translation_management->settings['doc_translation_method'] :
		ICL_TM_TMETHOD_MANUAL;

		return (string) ICL_TM_TMETHOD_MANUAL === (string) $doc_translation_method;
	}

	/**
	 * @param int     $post_id
	 *
	 * @return bool
	 */
	protected function is_post_using_tm_editor( $post_id ) {
		global $sitepress;
		$is_using_tm_editor = \WPML_TM_Post_Edit_TM_Editor_Mode::is_using_tm_editor( $sitepress, $post_id );

		return $is_using_tm_editor;
	}

	/**
	 * Enfold/Avia replaces "'" with "’" in enfold/onfig-templatebuilder/avia-template-builder/assets/js/avia-builder.js:1312
	 * We just follow the same replacement pattern for string translations
	 *
	 * @param null|string $translation
	 * @param bool        $is_attribute
	 *
	 * @return null|string
	 */
	public function replace_single_quotes( $translation, $is_attribute ) {
		if ( $translation && $is_attribute ) {
			$translation = preg_replace( "/'/", '’', $translation );
		}

		return $translation;
	}

	/**
	 * @param bool    $isBuiltWithShortcodes
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	public function isPostBuiltWithShortcodes( $isBuiltWithShortcodes, \WP_Post $post ) {
		return $isBuiltWithShortcodes || $this->is_active( $post->ID );
	}
}
