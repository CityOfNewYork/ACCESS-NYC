<?php

class WPML_Beaver_Builder_Accordion extends WPML_Beaver_Builder_Module_With_Items {

	protected function get_title( $field ) {
		switch( $field ) {
			case 'label':
				return esc_html__( 'Accordion Item Label', 'wpml-string-translation' );

			case 'content':
				return esc_html__( 'Accordion Item Content', 'wpml-string-translation' );

			default:
				return '';
		}
	}

}
