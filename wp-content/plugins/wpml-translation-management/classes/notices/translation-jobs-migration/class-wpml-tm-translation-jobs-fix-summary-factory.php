<?php

class WPML_TM_Translation_Jobs_Fix_Summary_Factory implements IWPML_Backend_Action_Loader {
	/**
	 * @return WPML_TM_Translation_Jobs_Fix_Summary
	 */
	public function create() {
		$template_service = new WPML_Twig_Template_Loader( array( WPML_TM_PATH . '/templates/translation-jobs-migration/' ) );

		return new WPML_TM_Translation_Jobs_Fix_Summary(
			new WPML_TM_Translation_Jobs_Fix_Summary_Notice( wpml_get_admin_notices(), $template_service->get_template() ),
			new WPML_TM_Jobs_Migration_State()
		);
	}
}