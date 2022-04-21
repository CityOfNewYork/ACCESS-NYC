<?php

class WPML_ST_Theme_Plugin_Localization_Options_Settings implements IWPML_Action {

	public function add_hooks() {
		add_filter( 'wpml_localization_options_settings', array( $this, 'add_st_settings' ) );
	}

	/**
	 * @param array $settings
	 *
	 * @return array
	 */
	public function add_st_settings( $settings ) {
		$settings[ WPML_ST_Themes_And_Plugins_Settings::OPTION_NAME ] = array(
			'settings_var' => WPML_ST_Themes_And_Plugins_Settings::OPTION_NAME,
			'filter'       => FILTER_SANITIZE_NUMBER_INT,
			'type'         => 'option',
			'st_setting'   => false,
		);
		$settings['use_theme_plugin_domain']                          = array(
			'settings_var' => 'use_header_text_domains_when_missing',
			'filter'       => FILTER_SANITIZE_NUMBER_INT,
			'type'         => 'setting',
			'st_setting'   => true,
		);

		return $settings;
	}
}
