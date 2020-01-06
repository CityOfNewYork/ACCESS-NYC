<?php

class WPML_ST_Theme_Plugin_Localization_Options_UI {
	/** @var array */
	private $st_settings;

	/**
	 * WPML_ST_Theme_Plugin_Localization_Options_UI constructor.
	 *
	 * @param array $st_settings
	 */
	public function __construct( $st_settings ) {
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
		$model['top_options'][] = array(
			'name'    => 'use_theme_plugin_domain',
			'type'    => 'checkbox',
			'value'   => 1,
			'label'   => __( 'Use theme or plugin text domains when gettext calls do not use a string literal', 'wpml-string-translation' ),
			'tooltip' => __( "Some themes and plugins don't properly set the textdomain (second argument) in GetText calls. When you select this option, WPML will assume that the strings found in GetText calls in the PHP files of the theme and plugin should have the textdomain with the theme/plugin's name.", 'wpml-string-translation' ),
			'checked' => checked( true, ! empty( $this->st_settings['use_header_text_domains_when_missing'] ), false ),
		);

		return $model;
	}
}
