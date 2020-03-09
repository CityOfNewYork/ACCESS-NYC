<?php

namespace WPML\ST\SlugTranslation\Hooks;

use WPML_Rewrite_Rule_Filter_Factory;
use WPML_ST_Slug_Translation_Settings_Factory;

class HooksFactory {
	/**
	 * We need a static property because there could be many instances of HooksFactory class but we need to guarantee
	 * there is only a single instance of Hooks
	 *
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * @return Hooks
	 */
	public function create() {
		if ( ! self::$instance ) {
			$settings_factory = new WPML_ST_Slug_Translation_Settings_Factory();
			$global_settings  = $settings_factory->create();

			self::$instance = new Hooks( new WPML_Rewrite_Rule_Filter_Factory(), $global_settings );
		}

		return self::$instance;
	}
}