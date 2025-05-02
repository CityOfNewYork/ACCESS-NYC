<?php

namespace WPML\PB\SiteOrigin;

use WPML\FP\Str;
use WPML\PB\TranslationJob\Groups;
use WPML_PB_String;

class RegisterStrings extends \WPML_Page_Builders_Register_Strings {

	public function register_strings_for_modules( array $data_array, array $package ) {
		foreach ( $data_array as $data ) {
			if ( isset( $data[ TranslatableNodes::SETTINGS_FIELD ] ) ) {
				if ( TranslatableNodes::isWrappingModule( $data ) ) {
					$this->register_strings_for_modules( $data[ TranslatableNodes::CHILDREN_FIELD ], $package );
				} else {
					$this->register_strings_for_node( $data[ TranslatableNodes::SETTINGS_FIELD ]['class'], $data, $package );
				}
			} elseif ( is_array( $data ) ) {
				$this->register_strings_for_modules( $data, $package );
			}
		}
	}

	/**
	 * @param WPML_PB_String $string
	 * @param string         $node_id
	 * @param mixed          $element
	 * @param array          $package
	 *
	 * @return WPML_PB_String
	 */
	protected function filter_string_to_register( WPML_PB_String $string, $node_id, $element, $package ) {
		if ( isset( $element['image'] ) ) {
			$string->set_title( Groups::appendImageIdToGroupLabel( $string->get_title(), (int) $element['image'] ) );
		}

		return $string;
	}

}
