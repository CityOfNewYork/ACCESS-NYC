<?php

use WPML\Plugins;

class AddTMAllowedOption extends WPML_Upgrade_Run_All {

	/**
	 * @return bool
	 */
	public function run() {
		add_action('init', [ Plugins::class, 'updateTMAllowedOption' ] );

		return true;
	}
}
