<?php

namespace WPML\TM\TranslationDashboard\EncodedFieldsValidation;

class FieldTitle {
	/**
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get( $slug ) {
		$string_slug = new \WPML_TM_Page_Builders_Field_Wrapper( $slug );

		return (string) $string_slug->get_string_title();
	}
}