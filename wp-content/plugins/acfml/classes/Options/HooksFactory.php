<?php

namespace ACFML\Options;

use function WPML\Container\make;

class HooksFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * @return \IWPML_Action[]
	 * @see    WPML_ACF::init_worker() as this factory has ACF requirements.
	 */
	public function create() {
		$hooks = [];

		if ( is_admin() ) {
			$hooks[] = make( EditorHooks::class );
		}

		if ( is_admin() || wpml_is_rest_request() ) {
			$hooks[] = new TranslationJobHooks();
		}

		$hooks[] = new CustomNamespacesHooks();

		return $hooks;
	}
}
