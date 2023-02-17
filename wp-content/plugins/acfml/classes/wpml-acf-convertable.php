<?php

interface WPML_ACF_Convertable {
	/**
	 * @param WPML_ACF_Field $acf_field
	 *
	 * @return mixed
	 */
	public function convert( WPML_ACF_Field $acf_field );
}
