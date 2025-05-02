<?php

namespace WPML\ST\ThemePluginLocalization;

use function WPML\Container\make;

class OtherLocalizationUIFactory {

	/**
	 * @return \WPML\ST\ThemePluginLocalization\OtherLocalizationUI
	 */
	public function create() {
		global $wpdb;

		$localization = new \WPML_Localization( $wpdb );
		$repo         = make( \WPML\ST\TranslationFile\FilesToScanRepository::class );

		return new OtherLocalizationUI( $localization, $repo );
	}
}
