<?php

class WPML_Cornerstone_Data_Settings implements IWPML_Page_Builders_Data_Settings {

	/**
	 * @return string
	 */
	public function get_meta_field() {
		return '_cornerstone_data';
	}

	/**
	 * @return string
	 */
	public function get_node_id_field() {
		return '_type';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_copy() {
		return array( '_cornerstone_settings', '_cornerstone_version', 'post_content' );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function convert_data_to_array( $data ) {
		$converted_data = $data;
		if ( is_array( $data ) ) {
			$converted_data = $data[0];
		}

		return json_decode( $converted_data, true );
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function prepare_data_for_saving( array $data ) {
		return wp_slash( wp_json_encode( $data ) );
	}

	/**
	 * @return string
	 */
	public function get_pb_name() {
		return 'Cornerstone';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_save() {
		return array( '_cornerstone_data' );
	}

	public function add_hooks() {
	}
}