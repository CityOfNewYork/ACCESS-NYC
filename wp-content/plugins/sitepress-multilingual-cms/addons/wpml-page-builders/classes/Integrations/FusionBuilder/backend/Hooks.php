<?php

namespace WPML\Compatibility\FusionBuilder\Backend;

class Hooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	public function add_hooks() {
		add_action( 'wp_update_nav_menu_item', [ $this, 'invalidateMegamenuHook' ], 1, 3 );
	}

	public function invalidateMegamenuHook() {
		if ( wpml_is_ajax() && isset( $_REQUEST['action'] ) && 'icl_msync_confirm' === $_REQUEST['action'] ) {
			global $mega_menu_framework;
			remove_action( 'wp_update_nav_menu_item', [ $mega_menu_framework::$classes['menus'], 'save_custom_menu_style_fields' ], 10 );
		}
	}
}