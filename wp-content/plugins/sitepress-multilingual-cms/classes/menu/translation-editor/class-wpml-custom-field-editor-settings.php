<?php

class WPML_Custom_Field_Editor_Settings {
	/** @var WPML_Custom_Field_Setting_Factory */
	private $settings_factory;

	public function __construct( WPML_Custom_Field_Setting_Factory $settingsFactory ) {
		$this->settings_factory = $settingsFactory;
	}

	public function filter_name( $fieldType, $default ) {
		return $this->settings_factory->post_meta_setting( $this->extractTypeName( $fieldType ) )->get_editor_label() ?: $default;
	}

	public function filter_style( $fieldType, $default ) {
		$filtered_style = $this->settings_factory->post_meta_setting( $this->extractTypeName( $fieldType ) )->get_editor_style();
		switch ( $filtered_style ) {
			case 'line':
				return 0;
			case 'textarea':
				return 1;
			case 'visual':
				return 2;
		}

		return $default;
	}

	public function get_group( $fieldType ) {
		return $this->settings_factory->post_meta_setting( $this->extractTypeName( $fieldType ) )->get_editor_group();
	}

	private function extractTypeName( $fieldType ) {
		return substr( $fieldType, strlen( 'field_' ) );
	}
}


