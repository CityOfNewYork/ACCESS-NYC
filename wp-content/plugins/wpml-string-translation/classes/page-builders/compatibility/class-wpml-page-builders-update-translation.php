<?php

/**
 * Class WPML_Page_Builders_Update_Translation
 */
abstract class WPML_Page_Builders_Update_Translation {

	const TRANSLATION_COMPLETE = 10;

	private $string_translations;
	private $lang;

	/**
	 * @var IWPML_Page_Builders_Translatable_Nodes
	 */
	protected $translatable_nodes;

	/**
	 * @var IWPML_Page_Builders_Data_Settings
	 */
	protected $data_settings;

	public function __construct(
		IWPML_Page_Builders_Translatable_Nodes $translatable_nodes,
		IWPML_Page_Builders_Data_Settings $data_settings ) {

		$this->data_settings = $data_settings;
		$this->translatable_nodes = $translatable_nodes;
	}

	/**
	 * @param int $translated_post_id
	 * @param $original_post
	 * @param $string_translations
	 * @param string $lang
	 */
	public function update( $translated_post_id, $original_post, $string_translations, $lang ) {
		$this->string_translations = $string_translations;
		$this->lang = $lang;

		$data = get_post_meta( $original_post->ID, $this->data_settings->get_meta_field(), true );
		$converted_data = $this->data_settings->convert_data_to_array( $data );

		$this->update_strings_in_modules( $converted_data );
		$this->save_data( $translated_post_id, $this->data_settings->get_fields_to_save(), $this->data_settings->prepare_data_for_saving( $converted_data ) );
		$this->copy_meta_fields( $translated_post_id, $original_post->ID, $this->data_settings->get_fields_to_copy() );

	}

	/**
	 * @param int $translated_post_id
	 * @param int $original_post_id
	 * @param array $meta_fields
	 */
	private function copy_meta_fields( $translated_post_id, $original_post_id, $meta_fields ) {
		foreach ( $meta_fields as $meta_key ) {
			$value = get_post_meta( $original_post_id, $meta_key, true );

			update_post_meta(
				$translated_post_id,
				$meta_key,
				apply_filters( 'wpml_pb_copy_meta_field', $value, $translated_post_id, $original_post_id, $meta_key )
			);
		}
	}

	/**
	 * @param int $post_id
	 * @param array $fields
	 * @param mixed $data
	 */
	private function save_data( $post_id, $fields, $data ) {
		foreach ( $fields as $field ) {
			update_post_meta( $post_id, $field, $data );
		}
	}

	/**
	 * @param WPML_PB_String $string
	 *
	 * @return WPML_PB_String
	 */
	protected function get_translation( WPML_PB_String $string ) {
		if ( array_key_exists( $string->get_name(), $this->string_translations ) &&
		     array_key_exists( $this->lang, $this->string_translations[ $string->get_name() ] ) ) {
			$translation = $this->string_translations[ $string->get_name() ][ $this->lang ];
			if ( (int) $translation['status'] === self::TRANSLATION_COMPLETE ) {
				$string->set_value( $translation['value'] );
			}
		}

		return $string;
	}

	abstract protected function update_strings_in_modules( array &$data_array );
	abstract protected function update_strings_in_node( $node_id, $settings );
}