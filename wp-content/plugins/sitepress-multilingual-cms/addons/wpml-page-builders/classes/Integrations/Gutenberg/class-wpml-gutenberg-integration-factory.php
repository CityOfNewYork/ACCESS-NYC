<?php

use function WPML\Container\make;
use function WPML\Container\share;

class WPML_Gutenberg_Integration_Factory {

	/** @return \WPML\PB\Gutenberg\Integration_Composite */
	public function create() {
		$integrations = new WPML\PB\Gutenberg\Integration_Composite();

		$mainIntegration = $this->create_gutenberg_integration();
		share( [ $mainIntegration ] );

		$integrations->add( $mainIntegration );

		if ( $this->should_translate_reusable_blocks() ) {
			$integrations->add(
				make( '\WPML\PB\Gutenberg\ReusableBlocks\Integration' )
			);

			if ( is_admin() ) {
				$integrations->add(
					make( '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration' )
				);
			}
		}

		if ( ! is_admin() ) {
			$integrations->add(
				make( \WPML\PB\Gutenberg\Widgets\Block\DisplayTranslation::class )
			);
			$integrations->add(
				make( \WPML\PB\Gutenberg\Widgets\Block\Search::class )
			);
			$integrations->add(
				make( \WPML\PB\Gutenberg\Navigation\Frontend::class )
			);
		}

		$integrations->add(
			make( \WPML\PB\Gutenberg\Widgets\Block\RegisterStrings::class )
		);

		return $integrations;
	}

	/**
	 * @return WPML_Gutenberg_Integration
	 */
	public function create_gutenberg_integration() {
		/**
		 * @var SitePress $sitepress
		 * @var wpdb $wpdb
		 */
		global $sitepress, $wpdb;

		$config_option    = new WPML_Gutenberg_Config_Option();
		$strings_in_block = $this->create_strings_in_block( $config_option );
		$string_factory   = new WPML_ST_String_Factory( $wpdb );

		$strings_registration = new WPML_Gutenberg_Strings_Registration(
			$strings_in_block,
			$string_factory,
			new WPML_PB_Reuse_Translations( $string_factory ),
			new WPML_PB_String_Translation( $wpdb ),
			make( 'WPML_Translate_Link_Targets' ),
			WPML\PB\TranslateLinks::getTranslatorForString( $string_factory, $sitepress->get_active_languages() )
		);

		return new WPML_Gutenberg_Integration(
			$strings_in_block,
			$config_option,
			$strings_registration
		);
	}

	private function create_strings_in_block( $config_option ) {
		$string_parsers = [
			new WPML\PB\Gutenberg\StringsInBlock\HTML( $config_option ),
			new WPML\PB\Gutenberg\StringsInBlock\Attributes( $config_option ),
		];

		return new WPML\PB\Gutenberg\StringsInBlock\Collection( $string_parsers );
	}

	/** @return bool */
	private function should_translate_reusable_blocks() {
		/** @var SitePress $sitepress */
		global $sitepress;

		return $sitepress->is_translated_post_type(
			WPML\PB\Gutenberg\ReusableBlocks\Translation::POST_TYPE
		);

	}
}
