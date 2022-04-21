<?php

namespace WPML\TM\ATE\Hooks;

use function WPML\Container\make;

class JobActionsFactory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	public function create() {
		return \WPML_TM_ATE_Status::is_enabled_and_activated()
			? new JobActions( make( \WPML_TM_ATE_API::class ) )
			: null;
	}
}