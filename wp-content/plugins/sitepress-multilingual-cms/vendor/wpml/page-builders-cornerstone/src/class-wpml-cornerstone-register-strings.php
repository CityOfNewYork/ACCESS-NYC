<?php

use WPML\PB\Cornerstone\Utils;

class WPML_Cornerstone_Register_Strings extends WPML_Page_Builders_Register_Strings {

	/**
	 * @param array $data_array
	 * @param array $package
	 */
	protected function register_strings_for_modules( array $data_array, array $package ) {
		foreach ( $data_array as $data ) {
			if ( isset( $data['_type'] ) && ! Utils::typeIsLayout( $data['_type'] ) ) {
				$this->register_strings_for_node( Utils::getNodeId( $data ), $data, $package );
			} elseif ( is_array( $data ) ) {
				$this->register_strings_for_modules( $data, $package );
			}
		}
	}

}
