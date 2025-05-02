<?php

namespace WPML\Compatibility\WPBakery\Hooks;

use WPML\LIB\WP\Hooks;

class Editor implements \IWPML_Frontend_Action, \IWPML_Backend_Action {
	public function add_hooks() {
		Hooks::onAction( 'vc_frontend_editor_render' )
			->then( [ $this, 'displayModalPageBuilderWarning' ] );
	}

	/**
	 * @return void
	 */
	public function displayModalPageBuilderWarning() {
		if ( is_user_logged_in() && isset( $_GET['post_id'] ) ) {
			do_action( 'wpml_maybe_display_modal_page_builder_warning', (int) $_GET['post_id'], 'WPBakery' );
		}
	}
}
