<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\LIB\WP\Hooks;

class WordPressWidgets implements \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onAction( 'wp_ajax_elementor_ajax' )
			->then( function() {
				add_filter( 'wpml_widget_language_selector_disable', '__return_true' );
			} );
	}
}
