<?php

class WPML_ACF_Gallery_Field extends WPML_ACF_Post_Object_Field {

	/**
	 * @return string|array
	 */
	public function convert_ids() {
		$ids = parent::convert_ids();

		if ( '' === $ids || is_null( $ids ) ) {
			return '';
		}

		return is_array( $ids ) || is_serialized( $ids ) ? $ids : [ $ids ];
	}
}
