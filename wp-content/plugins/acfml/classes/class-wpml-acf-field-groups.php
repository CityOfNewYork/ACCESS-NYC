<?php

use WPML\FP\Fns;
use WPML\FP\Obj;

class WPML_ACF_Field_Groups implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {
	/**
	 * @var SitePress
	 */
	private $sitepress;
	const POST_TYPE = 'acf-field-group';

	/**
	 * @var bool $nativeEditorEnabled
	 */
	private $nativeEditorEnabled;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		if ( is_admin()
			 && apply_filters( 'wpml_sub_setting', false, 'custom_posts_sync_option', self::POST_TYPE )
		) {
			add_filter( 'wpml_tm_post_edit_tm_editor_selector_display', [ $this, 'disable_tm_editor_selector_for_field_group' ] );
			add_action( 'admin_init', [ $this, 'translate_field_groups_with_wp_editor' ] );
		}
	}

	private function shouldUseNativeEditor() {
		if ( ! isset( $this->nativeEditorEnabled ) ) {
			/**
			 * Filters the TM editor setting for ACF field groups.
			 *
			 * @since 1.10.0
			 * @internal
			 *
			 * @param bool  $use_tm_editor Use TM editor for ACF field groups.
			 */
			$this->nativeEditorEnabled = (bool) apply_filters( 'acfml_use_native_editor_for_field_groups', true, get_the_ID() );
		}
		return $this->nativeEditorEnabled;
	}

	/**
	 * @param bool $display display TM editor selector.
	 * @return bool
	 */
	public function disable_tm_editor_selector_for_field_group( $display ) {
		$getPostType = function() {
			// phpcs:ignore WordPress.CSRF.NonceVerification.NoNonceVerification,WordPress.VIP.SuperGlobalInputUsage.AccessDetected
			$getFromPOST = Obj::prop( Fns::__, $_POST );

			return 'wpml_get_meta_boxes_html' === $getFromPOST( 'action' )
				? get_post_type( $getFromPOST( 'post_id' ) )
				: get_post_type();
		};

		return $this->shouldUseNativeEditor() && self::POST_TYPE === $getPostType() ? false : $display;
	}

	/**
	 * Set translation mode for acf-field-group post type to 'native editor'
	 */
	public function translate_field_groups_with_wp_editor() {
		if ( $this->shouldUseNativeEditor() ) {
			$tm_settings = apply_filters( 'wpml_setting', [], 'translation-management' );
			if ( ! Obj::pathOr( false, [ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE, self::POST_TYPE ], $tm_settings ) ) {
				$tm_settings[ WPML_TM_Post_Edit_TM_Editor_Mode::TM_KEY_FOR_POST_TYPE_USE_NATIVE ][ self::POST_TYPE ] = true;
				$this->sitepress->set_setting( 'translation-management', $tm_settings, true );
				WPML_TM_Post_Edit_TM_Editor_Mode::delete_all_posts_option( self::POST_TYPE );
			}
		}
	}
}
