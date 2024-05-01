<?php

namespace WPML\Upgrade\Commands;

class RemoveEndpointsOption extends \WPML_Upgrade_Run_All {

	public function run() {
		delete_option( 'wpml_registered_endpoints' );

		return true;
	}
}
