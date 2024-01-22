<?php

use WPML\PB\AutoUpdate\Settings as AutoUpdateSettings;
use WPML\PB\Elementor\DataConvert;

class WPML_Elementor_Data_Settings implements IWPML_Page_Builders_Data_Settings {

	const META_KEY_DATA = '_elementor_data';
	const META_KEY_MODE = '_elementor_edit_mode';

	/**
	 * @var WPML_Elementor_DB|null
	 */
	private $elementor_db;

	public function __construct( WPML_Elementor_DB $elementor_db = null ) {
		$this->elementor_db = $elementor_db;
	}

	public function add_hooks() {
		add_filter( 'wpml_custom_field_values_for_post_signature', array( $this, 'add_data_custom_field_to_md5' ), 10, 2 );
		add_filter( 'wpml_pb_copy_meta_field', array( $this, 'mark_css_field_as_empty' ), 10, 4 );

		if ( $this->elementor_db ) {
			add_action(
				'wpml_page_builder_string_translated',
				array( $this, 'save_post_body_as_plain_text' ),
				11,
				5
			);
		}
	}

	/**
	 * @param  array  $value
	 * @param  int    $translated_post_id
	 * @param  int    $original_post_id
	 * @param  string $meta_key
	 *
	 * @return mixed
	 */
	public function mark_css_field_as_empty( $value, $translated_post_id, $original_post_id, $meta_key ) {
		if ( '_elementor_css' === $meta_key && is_array( $value ) ) {
			if ( ! isset( $value['status'] ) ) {
				$value           = current( $value );
				$value['status'] = '';
				$value           = array( $value );
			} else {
				$value['status'] = '';
			}
		}

		return $value;
	}

	public function save_post_body_as_plain_text( $type, $post_id, $original_post, $string_translations, $lang ) {
		if ( $this->is_handling_post( $post_id ) ) {
			$this->elementor_db->save_plain_text( $post_id );
		}
	}

	/**
	 * @return string
	 */
	public function get_meta_field() {
		return self::META_KEY_DATA;
	}

	/**
	 * @return string
	 */
	public function get_node_id_field() {
		return 'id';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_copy() {
		return [
			'_elementor_version',
			self::META_KEY_MODE,
			'_elementor_css',
			'_elementor_template_type',
			'_elementor_template_widget_type',
			'_elementor_popup_display_settings',
		];
	}

	/**
	 * @param array|string $data
	 *
	 * @return array
	 */
	public function convert_data_to_array( $data ) {
		return DataConvert::unserialize( $data );
	}

	/**
	 * @param array $data
	 *
	 * @return string
	 */
	public function prepare_data_for_saving( array $data ) {
		return DataConvert::serialize( $data );
	}

	/**
	 * @return string
	 */
	public function get_pb_name() {
		return 'Elementor';
	}

	/**
	 * @return array
	 */
	public function get_fields_to_save() {
		return array( '_elementor_data' );
	}

	/**
	 * @param  array $custom_fields_values
	 * @param  int   $post_id
	 *
	 * @return array
	 */
	public function add_data_custom_field_to_md5( array $custom_fields_values, $post_id ) {
		if ( AutoUpdateSettings::isEnabled() ) {
			unset( $custom_fields_values[ $this->get_meta_field() ] );
		} else {
			$custom_fields_values[ $this->get_meta_field() ] = get_post_meta( $post_id, $this->get_meta_field(), true );
		}

		return $custom_fields_values;
	}

	/**
	 * @param int $postId
	 *
	 * @return bool
	 */
	public function is_handling_post( $postId ) {
		return (bool) get_post_meta( $postId, $this->get_meta_field(), true )
			&& self::is_edited_with_elementor( $postId );
	}

	/**
	 * @param int $postId
	 *
	 * @return bool
	 */
	public static function is_edited_with_elementor( $postId ) {
		return 'builder' === get_post_meta( $postId, self::META_KEY_MODE, true );
	}
}
