<?php

namespace WPML\Upgrade\Commands;

class RemoveRestDisabledNotice implements \IWPML_Upgrade_Command {

	public function run_admin() {
		wpml_get_admin_notices()->remove_notice( 'default', 'rest-disabled' );
		return true;
	}

	public function run_ajax() {}

	public function run_frontend() {}

	public function get_results() {
		return true;
	}
}
