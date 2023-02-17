<?php

namespace WPML\PB;

use function WPML\Container\make;

class LegacyIntegration {

	public static function load() {
		/** @var \SitePress $sitepress */
		global $sitepress;

		$integrationClasses = [];

		// WPBakery Page Builder (a.k.a. Visual Composer).
		if ( defined( 'WPB_VC_VERSION' ) ) {
			$wpml_visual_composer = new \WPML_Compatibility_Plugin_Visual_Composer( new \WPML_Debug_BackTrace( null, 12 ) );
			$wpml_visual_composer->add_hooks();

			$wpml_visual_composer_grid = new \WPML_Compatibility_Plugin_Visual_Composer_Grid_Hooks(
				$sitepress,
				new \WPML_Translation_Element_Factory( $sitepress )
			);
			$wpml_visual_composer_grid->add_hooks();

			make( \WPML\Compatibility\WPBakery\Styles::class )->add_hooks();
		}

		if ( defined( 'FUSION_BUILDER_VERSION' ) ) {
			$integrationClasses[] = \WPML_Compatibility_Plugin_Fusion_Hooks_Factory::class;
			$integrationClasses[] = \WPML\Compatibility\FusionBuilder\Frontend\Hooks::class;
			$integrationClasses[] = \WPML\Compatibility\FusionBuilder\Backend\Hooks::class;
			$integrationClasses[] = \WPML\Compatibility\FusionBuilder\DynamicContent::class;
			$integrationClasses[] = \WPML\Compatibility\FusionBuilder\FormContent::class;
			$integrationClasses[] = \WPML\Compatibility\FusionBuilder\Hooks\Editor::class;
		}

		if ( function_exists( 'avia_lang_setup' ) ) {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName
			global $iclTranslationManagement;
			$enfold = new \WPML_Compatibility_Theme_Enfold( $iclTranslationManagement );
			// phpcs:enable
			$enfold->init_hooks();
		}

		if ( defined( 'ET_BUILDER_THEME' ) || defined( 'ET_BUILDER_PLUGIN_VERSION' ) ) {
			$integrationClasses[] = \WPML_Compatibility_Divi::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\DynamicContent::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\Search::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\DiviOptionsEncoding::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\ThemeBuilderFactory::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\Builder::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\TinyMCE::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\DisplayConditions::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\DoubleQuotes::class;
			$integrationClasses[] = \WPML\Compatibility\Divi\Hooks\Editor::class;
		}

		$loader = new \WPML_Action_Filter_Loader();
		$loader->load( $integrationClasses );
	}
}
