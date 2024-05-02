<?php

namespace WPML\PB\Elementor\LanguageSwitcher;

use Elementor\Plugin;

class LanguageSwitcher implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		// @phpstan-ignore-next-line
		if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
			add_action( 'elementor/widgets/register', [ $this, 'registerWidgets' ] );
		} else {
			add_action( 'elementor/widgets/widgets_registered', [ $this, 'registerWidgetsDeprecated' ] );
		}

		add_filter( 'wpml_custom_language_switcher_is_enabled', '__return_true' );
	}

	/**
	 * @return void
	 */
	public function registerWidgets() {
		// @phpstan-ignore-next-line
		Plugin::instance()->widgets_manager->register( new Widget() );
	}

	/**
	 * Deprecated since Elementor 3.5.0.
	 *
	 * @return void
	 */
	public function registerWidgetsDeprecated() {
		// @phpstan-ignore-next-line
		Plugin::instance()->widgets_manager->register_widget_type( new Widget() );
	}
}
