<?php

class WPML_Beaver_Builder_Tab extends WPML_Beaver_Builder_Module_With_Items {

	protected function get_title( $field ) {
		switch( $field ) {
			case 'label':
				return esc_html__( 'Tab Item Label', 'wpml-string-translation' );

			case 'content':
				return esc_html__( 'Tab Item Content', 'wpml-string-translation' );

			default:
				return '';
		}
	}

}
