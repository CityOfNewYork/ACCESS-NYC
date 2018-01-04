<?php

class WPML_ST_Theme_Plugin_Localization_Options_UI {

	/** @var bool */
	private $new_strings_scan_enabled;

	/** @var array */
	private $st_settings;

	/**
	 * WPML_ST_Theme_Plugin_Localization_Options_UI constructor.
	 *
	 * @param bool $new_strings_scan_enabled
	 * @param array $st_settings
	 */
	public function __construct( $new_strings_scan_enabled, $st_settings ) {
		$this->new_strings_scan_enabled = $new_strings_scan_enabled;
		$this->st_settings = $st_settings;
	}

	public function add_hooks() {
		add_filter( 'wpml_localization_options_ui_model', array( $this, 'add_st_options' ) );
	}

	/**
	 * @param array $model
	 *
	 * @return array
	 */
	public function add_st_options( $model ) {
		$model['bottom_tittle']  = __( 'Other options:', 'wpml-string-translation' );
		$model['bottom_options'] = array(
			array(
				'name'    => 'use_theme_plugin_domain',
				'value'   => 1,
				'label'   => __( 'Use theme or plugin text domains when gettext calls do not use a string literal', 'wpml-string-translation' ),
				'tooltip' => __( "Some themes and plugins don't properly set the textdomain (second argument) in GetText calls. When you select this option, WPML will assume that the strings found in GetText calls in the PHP files of the theme and plugin should have the textdomain with the theme/plugin's name.", 'wpml-string-translation' ),
				'checked' => checked( true, ! empty( $this->st_settings['use_header_text_domains_when_missing'] ), false ),
			),
			array(
				'name'    => WPML_ST_Gettext_Hooks_Factory::ALL_STRINGS_ARE_IN_ENGLISH_OPTION,
				'value'   => 1,
				'label'   => __( 'Assume that all texts in PHP strings are in English', 'wpml-string-translation' ),
				'checked' => checked( true, get_option( WPML_ST_Gettext_Hooks_Factory::ALL_STRINGS_ARE_IN_ENGLISH_OPTION ), false ),
			),
		);

		return $model;
	}
}
