<?php

use WPML\TM\PostEditScreen\TranslationEditorPostSettings;

class WPML_TM_Post_Edit_TM_Editor_Select_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	public function create() {
		global $sitepress;

		if (
			$sitepress->is_post_edit_screen() ||
			wpml_is_ajax() ||
			apply_filters( 'wpml_enable_language_meta_box', false )
		) {
			return new TranslationEditorPostSettings( $sitepress );
		} else {
			return null;
		}
	}

}
