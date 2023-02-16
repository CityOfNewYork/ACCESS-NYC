<?php

use WPML\FP\Relation;
use function WPML\Container\make;

class WPML_Themes_Plugin_Localization_UI_Hooks_Factory implements IWPML_Backend_Action_Loader, IWPML_Deferred_Action_Loader {

	/** @return WPML_Theme_Plugin_Localization_UI_Hooks */
	public function create() {
		return Relation::propEq( 'id', WPML_PLUGIN_FOLDER . '/menu/theme-localization', get_current_screen() )
			? make( WPML_Theme_Plugin_Localization_UI_Hooks::class )
			: null;
	}

	/** @return string */
	public function get_load_action() {
		return 'current_screen';
	}
}
