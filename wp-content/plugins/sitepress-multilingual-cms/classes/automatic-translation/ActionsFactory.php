<?php

namespace WPML\TM\AutomaticTranslation\Actions;

use WPML\LIB\WP\User;
use function WPML\Container\make;
use WPML\Setup\Option;

class ActionsFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * @return Actions|null
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function create() {
		return \WPML_TM_ATE_Status::is_enabled_and_activated() && Option::shouldTranslateEverything()
			? make( Actions::class )
			: null;
	}
}
