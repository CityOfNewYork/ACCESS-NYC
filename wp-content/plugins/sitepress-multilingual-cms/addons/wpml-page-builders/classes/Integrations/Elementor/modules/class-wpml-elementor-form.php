<?php

/**
 * Class WPML_Elementor_Form
 */
class WPML_Elementor_Form extends WPML_Elementor_Module_With_Items {

	/**
	 * @return string
	 */
	public function get_items_field() {
		return 'form_fields';
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		return [
			'field_label',
			'placeholder',
			'field_html',
			'acceptance_text',
			'field_options',
			'step_next_label',
			'step_previous_label',
			'previous_button',
			'next_button',
		];
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_title( $field ) {
		return wpml_collect( [
			'field_label'            => esc_html__( 'Form: Field label', 'sitepress' ),
			'placeholder'            => esc_html__( 'Form: Field placeholder', 'sitepress' ),
			'field_html'             => esc_html__( 'Form: Field HTML', 'sitepress' ),
			'acceptance_text'        => esc_html__( 'Form: Acceptance Text', 'sitepress' ),
			'field_options'          => esc_html__( 'Form: Checkbox Options', 'sitepress' ),
			'step_next_label'        => esc_html__( 'Form: Step Next Label', 'sitepress' ),
			'step_previous_label'    => esc_html__( 'Form: Step Previous Label', 'sitepress' ),
			'previous_button'        => esc_html__( 'Form: Previous Button', 'sitepress' ),
			'next_button'            => esc_html__( 'Form: Next Button', 'sitepress' ),
		] )->get( $field, '' );
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_editor_type( $field ) {
		return wpml_collect( [
			'field_label'            => 'LINE',
			'placeholder'            => 'LINE',
			'field_html'             => 'VISUAL',
			'acceptance_text'        => 'LINE',
			'field_options'          => 'AREA',
			'step_next_label'        => 'LINE',
			'step_previous_label'    => 'LINE',
			'previous_button'        => 'LINE',
			'next_button'            => 'LINE',
		] )->get( $field, '' );
	}
}
