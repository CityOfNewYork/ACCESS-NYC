<?php

class WPML_WP_Options_General_Hooks implements IWPML_Action {

	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script(
			'wpml-options-general',
			ICL_PLUGIN_URL . '/dist/js/wp-options-general/app.js',
			array(),
			ICL_SITEPRESS_VERSION
		);

		$link    = '<a href="' . admin_url( 'admin.php?page=' . WPML_PLUGIN_FOLDER . '/menu/languages.php#lang-sec-1' ) . '">' .
		           /* translators: "WPML Site Languages section" is the title of the WPML settings page where administrators can configure the site's languages */
		           esc_html__( 'WPML Site Languages section', 'sitepress' ) .
		           '</a>';

		/* translators: "%s" will be replaced with a link to "WPML Site Languages section" page */
		$message = sprintf( __( 'When WPML is activated, the site language should be changed in the %s.', 'sitepress' ), $link );

		wp_localize_script( 'wpml-options-general', 'wpmlOptionsGeneral', array( 'languageSelectMessage' => $message ) );
	}

}
