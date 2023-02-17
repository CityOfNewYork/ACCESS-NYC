<?php
class WPML_ACF_Field_Annotations {

	/**
	 * @var WPML_ACF_Options_Page
	 */
	private $acf_options_page;

	/**
	 * @var WPML_ACF_Field_Settings
	 */
	private $acf_field_settings;

	/**
	 * WPML_ACF_Field_Annotations constructor.
	 *
	 * @param WPML_ACF_Options_Page   $options_page
	 * @param WPML_ACF_Field_Settings $field_settings
	 */
	public function __construct( WPML_ACF_Options_Page $options_page, WPML_ACF_Field_Settings $field_settings ) {
		$this->acf_options_page   = $options_page;
		$this->acf_field_settings = $field_settings;
	}

	/**
	 * Registers WP hooks related to field annotations.
	 */
	public function register_hooks() {
		if ( !defined( 'ACFML_HIDE_FIELD_ANNOTATIONS' ) || ACFML_HIDE_FIELD_ANNOTATIONS != true ) {
			add_action( 'acf/create_field', array( $this, 'acf_create_field' ), 10, 2 );
			add_action( 'acf/render_field', array( $this, 'acf_create_field' ), 10, 2 );
			add_filter( 'wpml_post_edit_settings_custom_field_description', array( $this, 'metabox_field_description' ), 10, 3 );
		}
	}

	/**
	 * @param array $field   The ACF field.
	 * @param mixed $post_id Current post ID.
	 */
	public function acf_create_field($field, $post_id = null) {
		if ( $this->acf_options_page->is_acf_options_page() ) {
			return;
		}

		if ( null === $post_id ) {
			$post_id = get_the_ID();
		}

		if ( $post_id ) {
			$this->field_original_value( $field, $post_id );
			$this->display_translated_warning( $field );
		}
	}

	/**
	 * Displays HTML code with information about original value of the field.
	 *
	 * @param array $field   The ACF field.
	 * @param mixed $post_id Current post ID.
	 */
	private function field_original_value($field, $post_id) {
		if ( $this->is_secondary_language() && 'repeater' !== $field['type'] ) {
			$custom_field_original_data = apply_filters( 'wpml_custom_field_original_data', false, $post_id, $field['_name'] );
			if ( ! empty( $custom_field_original_data['value'] ) && is_string( $custom_field_original_data['value'] ) ) {
				echo '<div class="wpml_acf_original_value">';
				echo '<strong>' . esc_html_x( 'Original', 'It is displayed before hint with value of ACF field in original language.', 'acfml' ) . ': </strong>';
				echo strip_tags( $custom_field_original_data['value'] );
				echo '</div>';
			}
		}
	}

	/**
	 * @param array $field
	 */
	private function display_translated_warning($field) {
		static $run_times = array();

		if ( ! isset( $field['key'] ) ) {
			return;
		}

		if (!isset($run_times[ $field['key'] ]) || $run_times[ $field['key'] ] == 0) {

			if ( $this->is_secondary_language() ) {

				$field_object = $this->resolve_field($field);

				if ( $field_object && $field_object->has_element_with_display_translated( false, $field ) ) {
					echo "<div class='wpml_acf_annotation ". $field_object->field_type() ."'>";
					_e("<strong>Warning</strong>: This field allows to select post type or taxonomy which you set in WPML translation options to 'Translatable - use translation if available or fallback to default language '. Whatever you set in this field for a secondary language post (this post) will be ignored and values from original post will be used (if you set to copy or duplicate value for this field).", "acfml");
					echo "</div>";
				}
			}
		}
		$run_times[ $field['key'] ] = isset( $run_times[ $field['key'] ] ) ? $run_times[ $field['key'] ] + 1 : 1;
	}

	private function is_secondary_language() {
		$current_language = apply_filters('wpml_current_language', null);
		$default_language = apply_filters('wpml_default_language', null);

		return $current_language != $default_language;
	}

	private function resolve_field($field) {

		$field_object = false;

		// stub data, not used in this context
		$processed_data = new stdClass();
		$processed_data->meta_value = null;
		$processed_data->target_lang = null;
		$processed_data->meta_data = null;
		$processed_data->related_acf_field_value = null;
		$ids_object = new stdClass();

		if (isset($field['class']) && $field['class'] == 'post_object') {
			$field_object = new WPML_ACF_Post_Object_Field($processed_data, $ids_object);
		} else if (isset($field['class']) && $field['class'] == 'page_link') {
			$field_object = new WPML_ACF_Page_Link_Field($processed_data, $ids_object);
		} else if (isset($field['class']) && $field['class'] == 'relationship') {
			$field_object = new WPML_ACF_Relationship_Field($processed_data, $ids_object);
		} else if (isset($field['class']) && $field['class'] == 'taxonomy') {
			$field_object = new WPML_ACF_Taxonomy_Field($processed_data, $ids_object);
		} else if (isset($field['class']) && $field['class'] ==  'gallery') {
			$field_object = new WPML_ACF_Post_Object_Field($processed_data, $ids_object);
		}


		return $field_object;
	}

	/**
	 * Displays description under custom field name in translation preferences metabox on post edit screen.
	 *
	 * @param string $description The current description where additional info would be added.
	 * @param string $name        Custom field name.
	 * @param int    $post_id     Edited post ID.
	 *
	 * @return string
	 */
	public function metabox_field_description( $description, $name, $post_id ) {

		$field_object = get_field_object( $name, $post_id );

		if ( $field_object && isset( $field_object['label'] ) && isset( $field_object['type'] ) ) {
			if ( $this->acf_field_settings->field_should_be_set_to_copy_once( $field_object ) ) {
				$field_data = [
					__( 'This type of ACF field will always be set to "Copy once".', 'acfml' ),
				];
			} else {
				$field_data = array(
					__( 'ACF field name:', 'acfml' ),
					$field_object['label'],
					__( 'ACF field type:', 'acfml' ),
					$field_object['type'],
				);
			}
			$description .= implode( ' ', $field_data );
		}

		return $description;
	}
}