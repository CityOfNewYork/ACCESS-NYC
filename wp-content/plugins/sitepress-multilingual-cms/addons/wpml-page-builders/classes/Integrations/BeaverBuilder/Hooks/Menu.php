<?php

namespace WPML\PB\BeaverBuilder\Hooks;

class Menu implements \IWPML_Frontend_Action {
	const TERM_TAXONOMY = 'nav_menu';

	public function add_hooks() {
		add_filter( 'fl_builder_menu_module_core_menu', [ $this, 'adjustTranslatedMenu' ], 10, 2 );
	}

	/**
	 * @param string $menu
	 * @param object $settings module settings object.
	 *
	 * @return string
	 */
	public function adjustTranslatedMenu( $menu, $settings ) {
		$targetMenuSlug = $settings->menu;

		$targetMenu = get_term_by( 'slug', $targetMenuSlug, self::TERM_TAXONOMY );
		if ( $targetMenu ) {
			$menu = $targetMenu->slug;
		}

		return $menu;
	}
}
