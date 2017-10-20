<?php

/**
 * Class WPML_Elementor_Slides
 */
class WPML_Elementor_Slides extends WPML_Elementor_Module_With_Items {

	/**
	 * @return string
	 */
	public function get_items_field() {
		return 'slides';
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		return array( 'heading', 'description', 'button_text' );
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_title( $field ) {
		switch( $field ) {
			case 'heading':
				return esc_html__( 'Slides: heading', 'wpml-string-translation' );

			case 'description':
				return esc_html__( 'Slides: description', 'wpml-string-translation' );

			case 'button_text':
				return esc_html__( 'Slides: button text', 'wpml-string-translation' );

			default:
				return '';
		}
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_editor_type( $field ) {
		switch( $field ) {
			case 'heading':
			case 'button_text':
				return 'LINE';

			case 'description':
				return 'VISUAL';

			default:
				return '';
		}
	}

}
