<?php

namespace WPML\TranslationManagement\Dashboard;

use WPML\UIPage;

/**
 * @since 4.7
 */
class Loader implements \IWPML_Backend_Action {

	public function add_hooks() {
		$isTmDashboardPage = UIPage::isTMDashboard( $_GET );

		if ( $isTmDashboardPage ) {
			// Enqueues the script only when the new WPML translation management dashboard admin page is visited
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ] );
		}
	}

	/**
	 * @since 4.7
	 */
	public function enqueueAdminScripts() {
		wp_enqueue_script(
			'wpml-tm-custom-events',
			WPML_TM_URL . '/dist/js/wpml-tm-dashboard-events/app.js',
			[],
			ICL_SITEPRESS_SCRIPT_VERSION,
			true
		);
	}

}
