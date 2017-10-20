<?php

class WPML_Beaver_Builder_Pricing_Table extends WPML_Beaver_Builder_Module_With_Items {

	public function &get_items( $settings ) {
		return $settings->pricing_columns;
	}

	public function get_fields() {
		return array( 'title', 'button_text', 'button_url', 'features', 'price', 'duration' );
	}

	protected function get_title( $field ) {
		switch ( $field ) {
			case 'title':
				return esc_html__( 'Pricing table: Title', 'wpml-string-translation' );

			case 'button_text':
				return esc_html__( 'Pricing table: Button text', 'wpml-string-translation' );

			case 'button_url':
				return esc_html__( 'Pricing table: Button link', 'wpml-string-translation' );

			case 'features':
				return esc_html__( 'Pricing table: Feature', 'wpml-string-translation' );

			case 'price':
				return esc_html__( 'Pricing table: Price', 'wpml-string-translation' );

			case 'duration':
				return esc_html__( 'Pricing table: Duration', 'wpml-string-translation' );

			default:
				return '';

		}
	}

	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'title':
			case 'button_text':
			case 'price':
			case 'duration':
				return 'LINE';

			case 'button_url':
				return 'LINK';

			case 'features':
				return 'VISUAL';

			default:
				return '';
		}
	}

}
