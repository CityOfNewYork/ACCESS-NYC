<?php

use WPML\PB\Elementor\Helper\Node;

/**
 * Class WPML_Elementor_Register_Strings
 */
class WPML_Elementor_Register_Strings extends WPML_Page_Builders_Register_Strings {

	/**
	 * @param array $data_array
	 * @param array $package
	 */
	protected function register_strings_for_modules( array $data_array, array $package ) {
		foreach ( $data_array as $data ) {
			if ( Node::isTranslatable( $data ) ) {
				$this->register_strings_for_node( $data[ $this->data_settings->get_node_id_field() ], $data, $package );
			}
			if ( Node::hasChildren( $data ) ) {
				$this->register_strings_for_modules( $data['elements'], $package );
			}
		}
	}
}
