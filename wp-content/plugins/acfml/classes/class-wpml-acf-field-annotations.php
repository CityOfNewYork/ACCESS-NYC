<?php

use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Type;

class WPML_ACF_Field_Annotations implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/**
	 * @var WPML_ACF_Options_Page
	 */
	private $acf_options_page;

	/**
	 * @var WPML_ACF_Field_Settings
	 */
	private $acf_field_settings;

	/**
	 * @var \ACFML\Field\Resolver
	 */
	private $fieldResolver;

	/**
	 * WPML_ACF_Field_Annotations constructor.
	 *
	 * @param WPML_ACF_Options_Page   $options_page
	 * @param WPML_ACF_Field_Settings $field_settings
	 * @param \ACFML\Field\Resolver   $fieldResolver
	 */
	public function __construct(
		WPML_ACF_Options_Page $options_page,
		WPML_ACF_Field_Settings $field_settings,
		\ACFML\Field\Resolver $fieldResolver
	) {
		$this->acf_options_page   = $options_page;
		$this->acf_field_settings = $field_settings;
		$this->fieldResolver      = $fieldResolver;
	}

	/**
	 * Registers WP hooks related to field annotations.
	 */
	public function add_hooks() {
		if ( ! defined( 'ACFML_HIDE_FIELD_ANNOTATIONS' ) || true !== ACFML_HIDE_FIELD_ANNOTATIONS ) {
			add_action( 'acf/create_field', [ $this, 'acf_create_field' ], 10, 2 );
			add_action( 'acf/render_field', [ $this, 'acf_create_field' ], 10, 2 );
			add_filter( 'wpml_post_edit_settings_custom_field_description', [ $this, 'metabox_field_description' ], 10, 3 );
		}
	}

	/**
	 * @param array $field   The ACF field.
	 * @param mixed $post_id Current post ID.
	 */
	public function acf_create_field( $field, $post_id = null ) {
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
	private function field_original_value( $field, $post_id ) {
		static $originalByKey = [];

		if ( ! $this->is_secondary_language() ) {
			return;
		}

		if ( 'repeater' === $field['type'] ) {
			return;
		}

		if ( Obj::prop( $field['key'], $originalByKey ) ) {
			return;
		}

		$custom_field_original_data = (array) apply_filters( 'wpml_custom_field_original_data', null, $post_id, $field['_name'] );

		if ( Type::isString( Obj::prop( 'value', $custom_field_original_data ) ) ) {
			echo '<div class="wpml_acf_original_value">';
			echo sprintf(
				/* translators: Displayed when editing an ACF field in a translation, showing its value in the original language; %1$s and %2$s turn the string into bold, and %3$s is the actual original value. */
				esc_html_x(
					'%1$sOriginal%2$s: %3$s',
					'Displayed when editing an ACF field in a translation, showing its value in the original language; %1$s and %2$s turn the string into bold, and %3$s is the actual original value.',
					'acfml'
				),
				'<strong>',
				'</strong>',
				esc_html( $custom_field_original_data['value'] )
			);
			echo '</div>';
		}

		$originalByKey[ $field['key'] ] = true;
	}

	/**
	 * @param array $field
	 */
	private function display_translated_warning( $field ) {
		static $warningByKey = [];

		if ( ! isset( $field['key'] ) ) {
			return;
		}

		if ( ! $this->is_secondary_language() ) {
			return;
		}

		if ( Obj::prop( $field['key'], $warningByKey ) ) {
			return;
		}

		$field_object = $this->resolve_field( $field );

		if ( $field_object->has_element_with_display_translated( false, $field ) ) {
			echo '<div class="wpml_acf_annotation ' . esc_attr( $field_object->field_type() ) . '">';
			echo sprintf(
				/* translators: Displayed when editing a relational ACF field in a translation when the related object is set to Display as Translated; %1$s and %2$s turn the string into bold. */
				esc_html_x(
					'%1$sWarning%2$s: This field allows to select post type or taxonomy which you set in WPML translation options to "Translatable - use translation if available or fallback to default language". Whatever you set in this field for a secondary language post (this post) will be ignored and values from original post will be used (if you set to copy or duplicate value for this field).',
					'Displayed when editing a relational ACF field in a translation when the related object is set to Display as Translated; %1$s and %2$s turn the string into bold.',
					'acfml'
				),
				'<strong>',
				'</strong>'
			);
			echo '</div>';
		}

		$warningByKey[ $field['key'] ] = true;
	}

	/**
	 * @return bool
	 */
	private function is_secondary_language() {
		$current_language = apply_filters( 'wpml_current_language', null );
		$default_language = apply_filters( 'wpml_default_language', null );

		return $current_language !== $default_language;
	}

	/**
	 * @param  array $field
	 *
	 * @return \WPML_ACF_Field
	 */
	private function resolve_field( $field ) {
		$processedData = new WPML_ACF_Processed_Data( null, '', [ 'type' => Obj::prop( 'type', $field ) ] );
		return $this->fieldResolver->run( $processedData );
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

		if ( ! $field_object ) {
			return $description;
		}

		if ( Logic::complement( Logic::both( Obj::prop( 'label' ), Obj::prop( 'type' ) ) )( $field_object ) ) {
			return $description;
		}

		if ( $this->acf_field_settings->field_should_be_set_to_copy_once( $field_object ) ) {
			$field_data = [
				__( 'This type of ACF field will always be set to "Copy once".', 'acfml' ),
			];
		} else {
			$field_data = [
				__( 'ACF field name:', 'acfml' ),
				$field_object['label'],
				__( 'ACF field type:', 'acfml' ),
				$field_object['type'],
			];
		}
		$description .= implode( ' ', $field_data );

		return $description;
	}
}
