<?php
class WPML_ACF_Term_Ids implements WPML_ACF_Convertable {
	/**
	 * Converts object (post, taxonomy, etc) ids in ACF field to their translated versions.
	 *
	 * @param WPML_ACF_Field $acf_field ACF field value.
	 *
	 * @return mixed id of translated object or serialized array of ids.
	 */
	public function convert( WPML_ACF_Field $acf_field ) {

		$came_serialized = is_serialized( $acf_field->meta_value );
		$came_as_array   = is_array( $acf_field->meta_value );

		$ids_unpacked = (array) maybe_unserialize( $acf_field->meta_value );

		$ids = array();
		foreach ( $ids_unpacked as $id ) {
			$ids[] = new WPML_ACF_Term_Id( $id, $acf_field );
		}

		$result = array_map(
			function( $id ) {
					return $id->convert()->id;
			}, $ids
		);

		if ( is_array( $result ) ) {
			if ( $came_serialized ) {
				return maybe_serialize( $result );
			} elseif ( $came_as_array ) {
				return $result;
			}
		}

		return $result[0];

	}
}
