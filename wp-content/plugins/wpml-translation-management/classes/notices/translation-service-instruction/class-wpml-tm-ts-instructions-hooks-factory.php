<?php

class WPML_TM_TS_Instructions_Hooks_Factory implements IWPML_Backend_Action_Loader, IWPML_AJAX_Action_Loader {
	/**
	 * @return WPML_TM_TS_Instructions_Hooks
	 */
	public function create() {
		return new WPML_TM_TS_Instructions_Hooks( $this->create_notice() );
	}

	/**
	 * @return WPML_TM_TS_Instructions_Notice
	 */
	private function create_notice() {
		$template_service = new WPML_Twig_Template_Loader( array( WPML_TM_PATH . '/templates/notices/translation-service-instruction/' ) );

		return new WPML_TM_TS_Instructions_Notice( wpml_get_admin_notices(), $template_service->get_template() );
	}
}