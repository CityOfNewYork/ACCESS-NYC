<?php

class WPML_TM_ATE_Translator_Message_Classic_Editor_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {

	/**
	 * @return \WPML_TM_ATE_Translator_Message_Classic_Editor|\IWPML_Action|null
	 */
	public function create() {
		global $wpdb;

		if ( $this->is_ajax_or_translation_queue() && $this->is_ate_enabled_and_manager_wizard_completed() && ! $this->is_editing_old_translation_and_te_is_used_for_old_translation() ) {

			$email_twig_factory = wpml_tm_get_email_twig_template_factory();

			return new WPML_TM_ATE_Translator_Message_Classic_Editor(
				new WPML_Translation_Manager_Records(
					$wpdb,
					wpml_tm_get_wp_user_query_factory(),
					wp_roles()
				),
				wpml_tm_get_wp_user_factory(),
				new WPML_TM_ATE_Request_Activation_Email(
					new WPML_TM_Email_Notification_View( $email_twig_factory->create() )
				)
			);
		}

		return null;
	}

	/**
	 * @return bool
	 */
	private function is_editing_old_translation_and_te_is_used_for_old_translation() {
		return array_key_exists( 'job_id', $_GET )
			   && filter_var( $_GET['job_id'], FILTER_SANITIZE_STRING )
			   && get_option( WPML_TM_Old_Jobs_Editor::OPTION_NAME ) === WPML_TM_Editors::WPML;
	}

	/**
	 * @return bool
	 */
	private function is_ate_enabled_and_manager_wizard_completed() {
		return WPML_TM_ATE_Status::is_enabled_and_activated() && (bool) get_option( WPML_TM_Wizard_Options::WIZARD_COMPLETE_FOR_MANAGER, false );
	}

	/**
	 * @return bool
	 */
	private function is_ajax_or_translation_queue() {
		return wpml_is_ajax() || WPML_TM_Page::is_translation_queue();
	}

}
