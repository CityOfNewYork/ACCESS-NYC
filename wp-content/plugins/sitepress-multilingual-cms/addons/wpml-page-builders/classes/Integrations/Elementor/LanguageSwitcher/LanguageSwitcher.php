<?php

namespace WPML\PB\Elementor\LanguageSwitcher;

use Elementor\Plugin;

class LanguageSwitcher implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'registerWidgets' ] );
		add_filter( 'wpml_custom_language_switcher_is_enabled', '__return_true' );
	}

	public function registerWidgets() {
		// @phpstan-ignore-next-line
		Plugin::instance()->widgets_manager->register_widget_type( new Widget() );
	}
}
