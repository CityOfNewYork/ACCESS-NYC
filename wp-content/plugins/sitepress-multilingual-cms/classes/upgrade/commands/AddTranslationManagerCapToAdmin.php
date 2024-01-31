<?php

namespace WPML\Upgrade\Commands;

use WPML\LIB\WP\User;

class AddTranslationManagerCapToAdmin extends \WPML_Upgrade_Run_All {

	protected function run() {
		get_role( 'administrator' )->add_cap( User::CAP_MANAGE_TRANSLATIONS );
		do_action( 'wpml_tm_ate_synchronize_managers' );
		wp_get_current_user()->get_role_caps(); // Refresh the current user capabilities.

		return true;
	}
}
