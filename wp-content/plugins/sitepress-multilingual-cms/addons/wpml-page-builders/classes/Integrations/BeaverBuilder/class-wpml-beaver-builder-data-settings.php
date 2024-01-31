<?php

use WPML\PB\BeaverBuilder\BeaverThemer\HooksFactory as BeaverThemer;

class WPML_Beaver_Builder_Data_Settings implements IWPML_Page_Builders_Data_Settings {

	/**
	 * @return string
	 */
	public function get_meta_field() {
		return '_fl_builder_data';
	}

	/**
	 * @return string
	 */
	public function get_node_id_field() {
		return 'node';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_copy() {
		$fields = [
			'_fl_builder_draft_settings',
			'_fl_builder_data_settings',
			'_fl_builder_enabled',
		];

		if ( BeaverThemer::isActive() ) {
			return array_merge(
				$fields,
				[
					'_fl_theme_builder_locations',
					'_fl_theme_builder_exclusions',
					'_fl_theme_builder_edit_mode',
				]
			);
		}

		return $fields;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function convert_data_to_array( $data ) {
		return $data;
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	public function prepare_data_for_saving( array $data ) {
		return $this->slash( $data );
	}

	/**
	 * @return string
	 */
	public function get_pb_name() {
		return 'Beaver builder';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_save() {
		return array( '_fl_builder_data', '_fl_builder_draft' );
	}

	public function add_hooks(){}

	/**
	 * Adds slashes to data going into the database as WordPress
	 * removes them when we save using update_metadata. This is done
	 * to ensure slashes in user input aren't removed.
	 *
	 * Inspired by `\FLBuilderModel::slash_settings`
	 *
	 * @param mixed $data The data to slash.
	 *
	 * @return mixed The slashed data.
	 */
	private function slash( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $val ) {
				$data[ $key ] = $this->slash( $val );
			}
		} elseif ( is_object( $data ) ) {
			foreach ( $data as $key => $val ) {
				$data->$key = $this->slash( $val );
			}
		} elseif ( is_string( $data ) ) {
			$data = wp_slash( $data );
		}

		return $data;
	}

	/**
	 * @param int $postId
	 *
	 * @return bool
	 */
	public function is_handling_post( $postId ) {
		return (bool) get_post_meta( $postId, '_fl_builder_enabled', true );
	}
}
