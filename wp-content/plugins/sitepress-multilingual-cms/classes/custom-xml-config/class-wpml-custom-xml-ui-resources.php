<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Custom_XML_UI_Resources {
	private $wpml_wp_api;

	/**
	 * @var string
	 */
	private $wpml_core_url;

	function __construct( WPML_WP_API $wpml_wp_api) {
		$this->wpml_wp_api   = $wpml_wp_api;
		$this->wpml_core_url = $this->wpml_wp_api->constant( 'ICL_PLUGIN_URL' );
	}

	function admin_enqueue_scripts() {
		if ( $this->wpml_wp_api->is_tm_page( 'custom-xml-config', 'settings' ) ) {
			$core_version = $this->wpml_wp_api->constant( 'ICL_SITEPRESS_VERSION' );

			$siteUrl = get_rest_url();

			wp_register_script( 'wpml-custom-xml-config', $this->wpml_core_url . '/dist/js/xmlConfigEditor/app.js', [], $core_version );

			wp_localize_script(
				'wpml-custom-xml-config',
				'wpmlCustomXML',
				[
					'restNonce' => wp_create_nonce( 'wp_rest' ),
					'endpoint'  => $siteUrl . 'wpml/v1/custom-xml-config',
				]
			);

			wp_register_style( 'wpml-custom-xml-config', $this->wpml_core_url . '/dist/css/xmlConfigEditor/styles.css', [], $core_version );


			wp_enqueue_style( 'wpml-custom-xml-config' );
			wp_enqueue_script( 'wpml-custom-xml-config' );
		}
	}
}
