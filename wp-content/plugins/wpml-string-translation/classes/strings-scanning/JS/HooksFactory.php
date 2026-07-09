<?php

namespace WPML\ST\StringsScanning\JS;

use WPML\FP\Obj;
use WPML\StringTranslation\Infrastructure\Setting\Repository\SettingsRepository;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		$hooks = [];

		$isDetectionEnabled = self::isDetectionEnabled();

		$shouldMonitorJSScripts = $isDetectionEnabled
		                          && ! wp_doing_ajax()
		                          && ! wp_doing_cron()
		                          && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );

		if ( $shouldMonitorJSScripts ) {
			$hooks[] = new ScriptRegisterHooks();
		}

		if ( is_admin() ) {
			$hooks[] = new SettingsHooks( $isDetectionEnabled );
		}

		return $hooks;
	}

	private static function isDetectionEnabled(): bool {
		/** @var \SitePress $sitepress */
		global $sitepress;

		return (bool) Obj::prop( SettingsRepository::DETECT_JS_STRINGS, (array) $sitepress->get_setting( 'st' ) );
	}
}
