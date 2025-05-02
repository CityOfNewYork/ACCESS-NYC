<?php

namespace WPML\TM\Menu\TranslationServices;

use WPML\Core\WP\App\Resources as AppResources;

class Resources implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( $this->is_active() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	public function enqueue_styles() {
		wp_enqueue_style(
			'wpml-tm-ts-admin-section',
			WPML_TM_URL . '/res/css/admin-sections/translation-services.css',
			array(),
			ICL_SITEPRESS_SCRIPT_VERSION
		);

		wp_enqueue_style(
			'wpml-tm-translation-services',
			WPML_TM_URL . '/dist/css/translationServices/styles.css',
			[],
			ICL_SITEPRESS_SCRIPT_VERSION
		);

		wp_enqueue_style(
			'wpml-unlisted-translation-service',
			WPML_TM_URL . '/res/css/admin-sections/unlisted-translation-service.css',
			array(),
			ICL_SITEPRESS_SCRIPT_VERSION
		);
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'wpml-tm-ts-admin-section',
			WPML_TM_URL . '/res/js/translation-services.js',
			array(),
			ICL_SITEPRESS_SCRIPT_VERSION
		);


		wp_enqueue_script(
			'wpml-tm-translation-services',
			WPML_TM_URL . '/dist/js/translationServices/app.js',
			array( AppResources::vendorAsDependency() ),
			ICL_SITEPRESS_SCRIPT_VERSION
		);

		wp_enqueue_script(
			'wpml-tp-api',
			WPML_TM_URL . '/res/js/wpml-tp-api.js',
			array( 'jquery', 'wp-util' ),
			ICL_SITEPRESS_SCRIPT_VERSION
		);

		$this->enqueue_script_unlisted_translation_service();
	}


	private function is_active() {
		return isset( $_GET['sm'] ) && 'translators' === $_GET['sm'];
	}

	/**
	 * @return void
	 */
	public function enqueue_script_unlisted_translation_service() {
		$handle = 'unlisted-translation-service';
		wp_enqueue_script(
			$handle,
			WPML_TM_URL . '/res/js/unlisted-translation-service.js',
			array(),
			ICL_SITEPRESS_SCRIPT_VERSION
		);

		wp_localize_script(
			$handle,
			'wpmlData',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( AuthenticationAjax::AJAX_ACTION ),
				'localization' => [
					'title'                => __( 'Activate a translation service', 'sitepress' ),
					'subtitle'             => __( 'Enter your service activation details below.', 'sitepress' ),
					'suid_label'           => __( 'Activation Key', 'sitepress' ),
					'enabled_service'      => __( 'The service has been enabled.', 'sitepress' ),
					'refresh_page'         => __( 'Refreshing the page, please wait...', 'sitepress' ),
					'server_error'         => __( 'Server error', 'sitepress' ),
					'something_went_wrong' => __( 'Something went wrong. Please try again.', 'sitepress' ),

				],
			]
		);
	}
}
