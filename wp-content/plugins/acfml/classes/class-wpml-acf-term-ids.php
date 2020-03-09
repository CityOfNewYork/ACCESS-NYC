<?php
class WPML_ACF_Term_Ids implements WPML_ACF_Convertable {
	/**
	 * Converts object (post, taxonomy, etc) ids in ACF field to their translated versions.
	 *
	 * @param WPML_ACF_Field $WPML_ACF_Field ACF field value
	 *
	 * @return mixed id of translated object or serialized array of ids
	 */
	public function convert( WPML_ACF_Field $WPML_ACF_Field ) {

		$came_serialized = is_serialized( $WPML_ACF_Field->meta_value );

		$ids_unpacked = (array) maybe_unserialize( $WPML_ACF_Field->meta_value );

		$ids = array();
		foreach ( $ids_unpacked as $id ) {
			$ids[] = new WPML_ACF_Term_Id( $id, $WPML_ACF_Field );
		}

		$result = array_map(
			function( $id ) {
					return $id->convert()->id;
			}, $ids
		);

		if ( count( $result ) === 1 && ! $came_serialized ) {
			return $result[0];
		}

		return maybe_serialize( $result );

	}
}
