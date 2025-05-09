<?php
/**
 * Class WPML_Elementor_Integration_Factory
 */
class WPML_Elementor_Integration_Factory {

	const SLUG = 'elementor';

	/**
	 * @return WPML_Page_Builders_Integration
	 */
	public function create() {

		$action_filter_loader = new WPML_Action_Filter_Loader();
		$action_filter_loader->load(
			array(
				'WPML_Elementor_Translate_IDs_Factory',
				'WPML_Elementor_URLs_Factory',
				'WPML_Elementor_Adjust_Global_Widget_ID_Factory',
				'WPML_PB_Elementor_Handle_Custom_Fields_Factory',
				'WPML_Elementor_Media_Hooks_Factory',
				'WPML_Elementor_WooCommerce_Hooks_Factory',
				\WPML\PB\Elementor\Hooks\WooCommerce::class,
				\WPML\PB\Elementor\LanguageSwitcher\LanguageSwitcher::class,
				\WPML\PB\Elementor\Hooks\DynamicElements::class,
				\WPML\PB\Elementor\Hooks\FormPopup::class,
				\WPML\PB\Elementor\Hooks\GutenbergCleanup::class,
				\WPML\PB\Elementor\Hooks\Frontend::class,
				\WPML\PB\Elementor\Hooks\DomainsWithMultisite::class,
				\WPML\PB\Elementor\Config\Factory::class,
				\WPML\PB\Elementor\Hooks\LandingPages::class,
				\WPML\PB\Elementor\Hooks\Editor::class,
				\WPML\PB\Elementor\Hooks\WordPressWidgets::class,
				\WPML\PB\Elementor\Hooks\Templates::class,
				\WPML\PB\Elementor\Hooks\Cache::class,
				\WPML_PB_Fix_Maintenance_Query::class,
				\WPML\PB\Elementor\Hooks\TranslationJobImages::class,
				\WPML\PB\Elementor\Hooks\CustomFonts::class,
				\WPML\PB\Elementor\Hooks\QueryFilter::class,
				\WPML\PB\Elementor\Hooks\SavePostActions::class,
				\WPML\PB\Elementor\Hooks\EditorLanguage::class,
				\WPML\PB\Elementor\Hooks\Shortcodes::class,
				\WPML\PB\Elementor\Hooks\DisplayConditions::class,
				\WPML\PB\Elementor\Config\IdsInWidgets::class,
				\WPML\PB\Elementor\Hooks\TranslationGuiLabels::class,
				\WPML\PB\Elementor\Hooks\DisambiguateMediaCarouselUrls::class,
			)
		);

		$nodes                = new WPML_Elementor_Translatable_Nodes();
		$elementor_db_factory = new WPML_Elementor_DB_Factory();
		$data_settings        = new WPML_Elementor_Data_Settings( $elementor_db_factory->create() );

		$string_registration_factory = new WPML_String_Registration_Factory( $data_settings->get_pb_name() );
		$string_registration         = $string_registration_factory->create();

		$register_strings   = new WPML_Elementor_Register_Strings( $nodes, $data_settings, $string_registration );
		$update_translation = new WPML_Elementor_Update_Translation( $nodes, $data_settings );

		return new WPML_Page_Builders_Integration( $register_strings, $update_translation, $data_settings );
	}
}
