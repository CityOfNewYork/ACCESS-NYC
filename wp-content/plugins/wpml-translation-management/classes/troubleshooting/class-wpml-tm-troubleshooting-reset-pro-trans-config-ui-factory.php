<?php

class WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI_Factory {

	/**
	 * Sets template base directory.
	 */
	private function get_template_base_dir() {
		return array(
			WPML_TM_PATH . '/templates/troubleshooting',
		);
	}

	/**
	 * Creates WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI instance
	 *
	 * @return WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI
	 */
	public function create() {
		$template_paths = $this->get_template_base_dir();

		$template_loader  = new WPML_Twig_Template_Loader( $template_paths );
		$template_service = $template_loader->get_template();

		return new WPML_TM_Troubleshooting_Reset_Pro_Trans_Config_UI( $template_service );
	}
}
