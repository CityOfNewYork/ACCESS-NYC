<?php

namespace WPML\Upgrade\Commands;

use ICL_AdminNotifier;

class RemoveTmWcmlPromotionNotice implements \IWPML_Upgrade_Command {

	public function run_admin() {
		ICL_AdminNotifier::remove_message( 'promote-wcml' );

		return true;
	}

	public function run_ajax() {}

	public function run_frontend() {}

	public function get_results() {
		return true;
	}
}
