<?php

class WPML_ACF_Options_Page {
	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var WPML_ACF_Worker
	 */
	private $acfWorker;

	/**
	 * @var string
	 */
	const ORIGINAL_ID = 'options';

	/**
	 * @var string
	 */
	const TRANSLATED_ID_FORMAT = 'options_%s';

	/**
	 * WPML_ACF_Options_Page constructor.
	 *
	 * @param SitePress       $sitepress
	 * @param WPML_ACF_Worker $acfWorker
	 */
	public function __construct( SitePress $sitepress, WPML_ACF_Worker $acfWorker ) {
		$this->sitepress = $sitepress;
		$this->acfWorker = $acfWorker;
	}

	/**
	 * Adds filters for displaying options page value and updating this value.
	 */
	public function register_hooks() {
		add_filter( 'acf/pre_render_fields', array( $this, 'fields_on_translated_options_page' ), 10, 2 );
		add_filter( 'acf/update_value', array( $this, 'overwrite_option_value' ), 10, 4 );

	}

	/**
	 * @return bool Tells if currently displayed page is ACF options page within wp-admin.
	 */
	public function is_acf_options_page() {
		$is = is_admin() && isset( $_GET['page'] ) && stristr( $_GET['page'], 'acf-options-' ) !== false;
		return $is;
	}

	/**
	 * Updates fields values on display at ACF options page.
	 *
	 * @param array $fields  All fields to be displayed on AF options page.
	 * @param mixed $post_id Post id or string identifying current option page.
	 *
	 * @return array
	 */
	public function fields_on_translated_options_page( $fields, $post_id = 0 ) {
		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = $this->get_field_options( $field, $post_id );
		}
		return $fields;
	}

	/**
	 * @param array $field
	 * @param mixed $post_id
	 *
	 * @return array
	 */
	private function get_field_options( array $field, $post_id ) {
		if ( isset( $field['wpml_cf_preferences'] ) && ! $this->is_repeater_field( $field ) ) {
			switch ( $field['wpml_cf_preferences'] ) {
				case WPML_COPY_CUSTOM_FIELD:
					if ( $this->is_field_on_translated_options_page( $post_id ) ) {
						$field['value'] = $this->convert_relationship_field( acf_get_value( self::ORIGINAL_ID, $field ), $field );

						$instructions = '';
						if ( isset( $field['instructions'] ) ) {
							$instructions = $field['instructions'] . '<br />';
						}
						$instructions .= __( 'This value will be always replaced with the original, because its translation preferences are set to copy.', 'acfml' );

						$field['instructions'] = $instructions;
					}
					break;
				case WPML_COPY_ONCE_CUSTOM_FIELD:
					if ( null === $field['value'] ) {
						// acf_get_value returns value or empty string if value not set yet.
						$field['value'] = $this->convert_relationship_field( acf_get_value( $post_id, $field ), $field );
					}
					if ( empty( $field['value'] ) && $this->is_field_on_translated_options_page( $post_id ) ) { // so if value not set yet, get value from original language.
						$field['value'] = $this->convert_relationship_field( acf_get_value( self::ORIGINAL_ID, $field ), $field );
					}
					break;
			}
		}

		return $field;
	}


	/**
	 * Updates options page's value during save to assure when field is set to Copy, it always stores default value.
	 *
	 * @param mixed  $value          The field's value.
	 * @param mixed  $post_id        Post id or string identifying current option page.
	 * @param array  $field          Field metadata.
	 * @param string $original_value The field's value (again, the same as in first param).
	 *
	 * @return mixed
	 */
	public function overwrite_option_value( $value, $post_id = 0, $field = array(), $original_value = '' ) {
		if ( ! $this->is_repeater_field( $field )
				&& isset( $field['wpml_cf_preferences'] )
				&& WPML_COPY_CUSTOM_FIELD === $field['wpml_cf_preferences']
				&& $this->is_field_on_translated_options_page( $post_id )
		) {
			$value = $this->convert_relationship_field( acf_get_value( self::ORIGINAL_ID, $field ), $field );
		}
		return $value;
	}

	/**
	 * @param mixed $post_id Post id or string identifying current option page.
	 *
	 * @return bool
	 */
	private function is_field_on_translated_options_page( $post_id ) {
		$on_translated_page = false;
		if ( ! is_numeric( $post_id ) ) {
			$current_language   = $this->sitepress->get_current_language();
			$default_language   = $this->sitepress->get_default_language();
			$expected_post_id   = sprintf( self::TRANSLATED_ID_FORMAT, $current_language );
			$on_translated_page = $current_language !== $default_language && $expected_post_id === $post_id;
		}
		return $on_translated_page;
	}

	/**
	 * If field has numeric relationship value, replace with translated version.
	 *
	 * @param mixed $value Current field value.
	 * @param array $field ACF field.
	 *
	 * @return mixed
	 */
	private function convert_relationship_field( $value, $field ) {
		if ( is_numeric( $value ) ) {
			$current_language = $this->sitepress->get_current_language();
			$data             = $this->acfWorker->prepare_metadata( $value, $field['name'], self::ORIGINAL_ID, sprintf( self::TRANSLATED_ID_FORMAT, $current_language ) );
			$value            = $this->acfWorker->duplicate_post_meta( $value, $current_language, $data );
		}
		return $value;
	}

	/**
	 * Checks if given field has repeater field type.
	 *
	 * @param array $field ACF field data.
	 *
	 * @return bool
	 */
	private function is_repeater_field( $field ) {
		return isset( $field['type'] ) && 'repeater' === $field['type'];
	}
}
