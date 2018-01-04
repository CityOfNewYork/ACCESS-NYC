<?php

class WPML_ST_Theme_Plugin_Localization_Options_UI_Factory implements IWPML_Backend_Action_Loader, IWPML_Deferred_Action_Loader {

	/** @return WPML_ST_Theme_Plugin_Localization_Options_UI */
	public function create() {
		global $sitepress;

		$hooks = null;
		$current_screen = get_current_screen();

		if ( isset( $current_screen->id ) && WPML_PLUGIN_FOLDER . '/menu/theme-localization' === $current_screen->id ) {
			$theme_plugin_localization_settings = new WPML_ST_Themes_And_Plugins_Settings();
			$hooks = new WPML_ST_Theme_Plugin_Localization_Options_UI(
				$theme_plugin_localization_settings->must_display_notices(),
				$sitepress->get_setting( 'st' )
			);
		}

		return $hooks;
	}

	/** @return string */
	public function get_load_action() {
		return 'current_screen';
	}
}