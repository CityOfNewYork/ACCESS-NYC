<?php
class WPML_ACF_Post_Ids implements WPML_ACF_Convertable {

	/**
	 * @param WPML_ACF_Field $acf_field
	 *
	 * @return string[]|string - should always be string[], string only when meta_value is "serialized twice"
	 */
	public function convert( WPML_ACF_Field $acf_field ) {

		$came_serialized = is_serialized( $acf_field->meta_value );

		$ids_unpacked = maybe_unserialize( $acf_field->meta_value );

		if ( ! is_array( $ids_unpacked ) ) {
			$ids_unpacked = [ $ids_unpacked ];
		}

		$ids = [];
		foreach ( $ids_unpacked as $id ) {
			$ids[] = new WPML_ACF_Post_Id( $id, $acf_field );
		}

		$result = array_map(
			function ( $id ) {
				return (string) $id->convert()->id;
			}, $ids
		);

		if ( $came_serialized ) {
			$result = maybe_serialize( $result );
		}

		return $result;
	}
}
