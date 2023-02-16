<?php

class WPML_Theme_Plugin_Localization_Options_UI implements IWPML_Theme_Plugin_Localization_UI_Strategy {

	/** @var SitePress */
	private $sitepress;

	public function __construct( SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/** @return array */
	public function get_model() {
		$model = array(
			'nonce_field'            => WPML_Theme_Plugin_Localization_Options_Ajax::NONCE_LOCALIZATION_OPTIONS,
			'nonce_value'            => wp_create_nonce( WPML_Theme_Plugin_Localization_Options_Ajax::NONCE_LOCALIZATION_OPTIONS ),
			'section_label'          => __( 'Localization options', 'sitepress' ),
			'top_options'            => array(
				array(
					'template' => 'automatic-load-check.twig',
					'model'    => array(
						'theme_localization_load_textdomain' => array(
							'value'   => 1,
							'label'   => __( "Automatically load the theme's .mo file using 'load_textdomain'", 'sitepress' ),
							'checked' => checked( $this->sitepress->get_setting( 'theme_localization_load_textdomain' ), true, false ),
						),
						'gettext_theme_domain_name' => array(
							'value' => $this->sitepress->get_setting( 'gettext_theme_domain_name', '' ),
							'label' => __( 'Enter textdomain:', 'sitepress' ),
						),
					),
				),
			),
			'button_label'           => __( 'Save', 'sitepress' ),
			'scanning_progress_msg'  => __( "Scanning now, please don't close this page.", 'sitepress' ),
			'scanning_results_title' => __( 'Scanning Results', 'sitepress' ),
		);

		return apply_filters( 'wpml_localization_options_ui_model', $model );
	}

	/** @return string */
	public function get_template() {
		return 'options.twig';
	}
}
