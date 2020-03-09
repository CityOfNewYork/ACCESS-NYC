<?php

class WPML_ACF_Duplicated_Post {

	/**
	 * @param \WPML_ACF_Processed_Data $processed_data
	 *
	 * @return \WPML_ACF_Field
	 */
	public function resolve_field( WPML_ACF_Processed_Data $processed_data ) {
		return $this->get_field_object( $processed_data, new WPML_ACF_Void_Field($processed_data) );
	}

	/**
	 * @param \WPML_ACF_Processed_Data $processed_data
	 * @param \WPML_ACF_Field          $field
	 *
	 * @return \WPML_ACF_Field
	 */
	private function get_field_object( WPML_ACF_Processed_Data $processed_data, $field ) {
		if ( isset( $processed_data->meta_data['master_post_id'], $processed_data->meta_data['key'] ) ) {
			$acf_field_object = get_field_object( $processed_data->meta_data['key'], $processed_data->meta_data['master_post_id'] );

			if ( isset ( $acf_field_object['type'] ) ) {
				if ( 'post_object' ===  $acf_field_object['type'] ) {
					$field = new WPML_ACF_Post_Object_Field( $processed_data, new WPML_ACF_Post_Ids() );
				} elseif ( 'page_link' ===  $acf_field_object['type'] ) {
					$field = new WPML_ACF_Page_Link_Field( $processed_data, new WPML_ACF_Post_Ids() );
				} elseif ( 'relationship' ===  $acf_field_object['type'] ) {
					$field = new WPML_ACF_Relationship_Field( $processed_data, new WPML_ACF_Post_Ids() );
				} elseif ( 'taxonomy' ===  $acf_field_object['type'] ) {
					$field = new WPML_ACF_Taxonomy_Field( $processed_data, new WPML_ACF_Term_Ids() );
				} elseif ( 'gallery' ===  $acf_field_object['type'] ) {
					$field = new WPML_ACF_Post_Object_Field( $processed_data, new WPML_ACF_Post_Ids() );
				}
			}
		}
		return $field;
	}
}
