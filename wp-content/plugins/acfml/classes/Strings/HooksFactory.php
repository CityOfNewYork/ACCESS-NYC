<?php

namespace ACFML\Strings;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 */
	public function create() {
		$factory    = new Factory();
		$translator = new Translator( $factory );

		$hooks = [];

		if ( self::isWpmlSetupComplete() ) {
			$hooks[] = new STPluginHooks( $translator );
		}

		if ( self::isStActivated() ) {
			$hooks[] = new FieldHooks( $factory, $translator );
			$hooks[] = new CptHooks( $factory, $translator );
			$hooks[] = new TaxonomyHooks( $factory, $translator );
			$hooks[] = new OptionsPageHooks( $factory, $translator );
			$hooks[] = new TranslationJobHooks( $factory );
			$hooks[] = new TranslateEverythingHooks();
		}

		return $hooks;
	}

	/**
	 * @return bool
	 */
	public static function isStActivated() {
		return defined( 'WPML_ST_VERSION' );
	}

	/**
	 * @return bool
	 */
	public static function isWpmlSetupComplete() {
		return (bool) apply_filters( 'wpml_setting', false, 'setup_complete' );
	}
}
