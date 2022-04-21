<?php

class WPML_TM_Old_Editor implements IWPML_Action {
	const ACTION = 'icl_ajx_custom_call';

	const CUSTOM_AJAX_CALL = 'icl_doc_translation_method';

	const NOTICE_ID = 'wpml-translation-management-old-editor';

	const NOTICE_GROUP = 'wpml-translation-management';

	public function add_hooks() {
		add_action( self::ACTION, array( $this, 'handle_custom_ajax_call' ), 10, 2 );
	}

	public function handle_custom_ajax_call( $call, $data ) {
		if ( self::CUSTOM_AJAX_CALL === $call ) {
			if ( ! isset( $data[ WPML_TM_Old_Jobs_Editor::OPTION_NAME ] ) ) {
				return;
			}

			$old_editor = $data[ WPML_TM_Old_Jobs_Editor::OPTION_NAME ];

			if ( ! in_array( $old_editor, array( WPML_TM_Editors::WPML, WPML_TM_Editors::ATE ), true ) ) {
				return;
			}

			update_option( WPML_TM_Old_Jobs_Editor::OPTION_NAME, $old_editor );

			if ( WPML_TM_Editors::WPML === $old_editor && $this->is_ate_enabled_and_manager_wizard_completed() ) {
				$text   = __( 'You activated the Advanced Translation Editor for this site, but you are updating an old translation. WPML opened the Standard Translation Editor, so you can update this translation. When you translate new content, you\'ll get the Advanced Translation Editor with all its features. To change your settings, go to WPML Settings.', 'sitepress' );
				$notice = new WPML_Notice( self::NOTICE_ID, $text, self::NOTICE_GROUP );
				$notice->set_css_class_types( 'notice-info' );
				$notice->set_dismissible( true );
				$notice->add_display_callback( 'WPML_TM_Page::is_translation_editor_page' );
				wpml_get_admin_notices()->add_notice( $notice, true );
			} else {
				wpml_get_admin_notices()->remove_notice( self::NOTICE_GROUP, self::NOTICE_ID );
			}
		}
	}

	/**
	 * @return bool
	 */
	private function is_ate_enabled_and_manager_wizard_completed() {
		return WPML_TM_ATE_Status::is_enabled_and_activated() && (bool) get_option( WPML_TM_Wizard_Options::WIZARD_COMPLETE_FOR_MANAGER, false );
	}

}
