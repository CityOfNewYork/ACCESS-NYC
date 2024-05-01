<?php

use WPML\TM\API\Basket;

class WPML_Dashboard_Ajax {

	public function enqueue_js() {
		wp_register_script(
			'wpml-tm-dashboard-scripts',
			WPML_TM_URL . '/res/js/tm-dashboard/wpml-tm-dashboard.js',
			array( 'jquery', 'backbone', 'wpml-tm-progressbar' ),
			ICL_SITEPRESS_VERSION
		);
		$wpml_tm_strings = $this->get_wpml_tm_script_js_strings();
		wp_localize_script( 'wpml-tm-dashboard-scripts', 'wpml_tm_strings', $wpml_tm_strings );
		wp_enqueue_script( 'wpml-tm-dashboard-scripts' );

		wp_enqueue_script( OTGS_Assets_Handles::POPOVER_TOOLTIP );
		wp_enqueue_style( OTGS_Assets_Handles::POPOVER_TOOLTIP );
	}

	private function get_wpml_tm_script_js_strings() {
		$wpml_tm_strings = array(
			'BB_default'                     => Basket::shouldUse()
				? __( 'Add selected content to translation basket', 'wpml-translation-management' )
				: __( 'Translate selected content', 'wpml-translation-management' ),
			'BB_mixed_actions'               => __(
				'Add selected content to translation basket / Duplicate',
				'wpml-translation-management'
			),
			'BB_duplicate_all'               => __( 'Duplicate', 'wpml-translation-management' ),
			'BB_no_actions'                  => __(
				'Choose at least one translation action',
				'wpml-translation-management'
			),
			'duplication_complete'           => __(
				'Finished Post Duplication',
				'wpml-translation-management'
			),
			'wpml_duplicate_dashboard_nonce' => wp_create_nonce( 'wpml_duplicate_dashboard_nonce' ),
			'wpml_need_sync_message_nonce'   => wp_create_nonce( 'wpml_need_sync_message_nonce' ),
			'duplicating'                    => __( 'Duplicating', 'wpml-translation-management' ),
			'post_parent'                    => __( 'Post parent', 'wpml-translation-management' ),
			'any'                            => __( 'Any', 'wpml-translation-management' ),
		);

		return $wpml_tm_strings;
	}
}
