<?php

use WPML\Core\WP\App\Resources;

class WPML_WP_Options_General_Hooks implements IWPML_Action {

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script(
			'wpml-options-general',
			ICL_PLUGIN_URL . '/dist/js/wp-options-general/app.js',
			array( Resources::vendorAsDependency() ),
			ICL_SITEPRESS_SCRIPT_VERSION
		);

		$site_language_link = '<a href="' . admin_url( 'admin.php?page=' . WPML_PLUGIN_FOLDER . '/menu/languages.php#lang-sec-1' ) . '">' .
			/* translators: "WPML Site Languages section" is the title of the WPML settings page where administrators can configure the site's languages */
			esc_html__( 'WPML Site Languages section', 'sitepress' ) .
			'</a>';

		$profile_language_link = '<a href="' . admin_url( 'profile.php' ) . '">' .
			/* translators: "Language section" refers to the language settings in user profile */
			esc_html__( 'Language section', 'sitepress' ) .
			'</a>';

		$message = sprintf(
		/* translators: %1$s will be replaced with link to "WPML Site Languages section", %2$s will be replaced with link to profile "Language section" */
            __( 'With WPML activated, you can set your site’s languages from the %1$s.<br>To change the language of your WordPress admin, go to the %2$s in your user profile.', 'sitepress' ),
			$site_language_link,
			$profile_language_link
		);

		wp_localize_script( 'wpml-options-general', 'wpmlOptionsGeneral', array( 'languageSelectMessage' => $message ) );
	}

}
