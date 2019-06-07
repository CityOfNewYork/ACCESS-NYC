<?php

class WPML_ACF_Field_Settings {
	public function __construct( TranslationManagement $iclTranslationManagement ) {
		$this->iclTranslationManagement = $iclTranslationManagement;

		// add radio buttons on Field Group page
		add_action( 'acf/render_field_settings', array( $this, 'render_field_settings'), 10, 1);

		// same as above run when user is changing field type on field group edit screen
		if ( function_exists( 'acf_maybe_get_POST' ) ) {
			$field = acf_maybe_get_POST('field');
			if ( isset( $field['type'] ) ) {
				add_action( "acf/render_field_settings/type={$field['type']}", array( $this, 'render_field_settings'), 10, 1);
			}
		}

		// handle setting sync preferences on Field Group page
		add_filter( 'acf/update_field', array( $this, 'update_field_settings' ), 10, 1);

		// when user adds new field value on post edit screen
		add_filter( 'acf/update_value', array( $this, 'field_value_updated'), 10, 4 );

		// use case when user updates sync prefernces on post edit screen
		add_action( 'wpml_single_custom_field_sync_option_updated', array($this, 'user_set_sync_preferences'), 10, 1);
		add_action( 'wpml_custom_fields_sync_option_updated', array($this, 'user_set_sync_preferences'), 10, 1);

		// mark field as not migrated yet
		add_filter( "acf/get_field_label", array($this, "mark_not_migrated_field"), 10, 2);

	}

	public function render_field_settings( $field ) {
		acf_render_field_setting( $field, array(
			'label'			=> __('Translation preferences','acfml'),
			'instructions'	=> __('What to do with field\'s value when post/page is going to be translated','acf'),
			'type'			=> 'radio',
			'name'			=> 'wpml_cf_preferences',
			'layout'		=> 'horizontal',
			'choices'		=> array(
				WPML_IGNORE_CUSTOM_FIELD	=> __("Don't translate",'acfml'),
				WPML_COPY_CUSTOM_FIELD		=> __("Copy",'acfml'),
				WPML_COPY_ONCE_CUSTOM_FIELD => __("Copy once", 'acfml'),
				WPML_TRANSLATE_CUSTOM_FIELD => __("Translate", "acfml")
			)
		));
	}

	public function update_field_settings( $field ) {

		if ( isset( $field['wpml_cf_preferences'] ) && isset( $field['name'] ) ) {
			$this->update_existing_subfields( $field );
			$this->iclTranslationManagement->settings[ 'custom_fields_translation' ][ $field['name'] ] = $field['wpml_cf_preferences'];
			$this->iclTranslationManagement->save_settings();
		}

		return $field;
	}

	public function field_value_updated( $value, $post_id, $field, $_value = null ) {

		if ( isset( $field['wpml_cf_preferences'] ) && isset( $field['name'] ) ) {
			if ( !isset( $this->iclTranslationManagement->settings[ 'custom_fields_translation' ][ $field['name'] ] ) ) {
				$this->iclTranslationManagement->settings[ 'custom_fields_translation' ][ $field['name'] ] = $field['wpml_cf_preferences'];
				$this->iclTranslationManagement->save_settings();
			}
		}

		return $value;
	}

	public function user_set_sync_preferences($cft) {

		foreach ( $cft as $field_name => $field_preferences ) {
			$post_id = $this->get_post_with_custom_field( $field_name );
			$field_object = get_field_object( $field_name, $post_id );

			if ( $field_object ) {
				if ( $field_object['wpml_cf_preferences'] != $field_preferences ) {
					$field_post = get_post( $field_object['ID'] );
					$field_post_content = maybe_unserialize( $field_post->post_content );
					$field_post_content['wpml_cf_preferences'] = $field_preferences;
					wp_update_post( array(
						'ID' => $field_object['ID'],
						'post_content' => maybe_serialize( $field_post_content )
					) );
				}
			}
		}

		// this action runs also for case 'icl_tcf_translation', @see \TranslationManagement::ajax_calls
		// it shouldn't because it will overwrite normal cf fields values with zeros
		remove_action( 'wpml_custom_fields_sync_option_updated', array( $this, 'user_set_sync_preferences' ), 10, 1);
	}

	private function get_post_with_custom_field($field_name) {
		$post_id = get_the_ID() || get_queried_object();
		if (!is_numeric($post_id)) {
			global $wpdb;
			$query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '{$field_name}' LIMIT 1";
			$post_id = $wpdb->get_var($query);
		}
		return $post_id;
	}

	private function update_existing_subfields( $field ) {
		if ( isset( $field['parent'] ) ) {
			$parent_post_type = get_post_type( $field['parent'] );
			if ( "acf-field" == $parent_post_type) { // yes, it is subfield
				global $wpdb;
				$query = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key LIKE %s";
				$prepared = $wpdb->prepare( $query, "%" . $wpdb->esc_like( $field['name'] ) );
				$query_result = $wpdb->get_results( $prepared ); // all custom fields from postmeta created on base of this ACF subfield
				$handled_fields = array();
				if ( is_array( $query_result ) && !empty( $query_result ) ) {
					foreach ( $query_result as $custom_field ) {
						if ( !in_array( $custom_field->meta_key, $handled_fields ) ) {
							$handled_fields[] = $custom_field->meta_key;
							if ( substr( $custom_field->meta_key, 0, 1 ) !== "_" ) { // this is not a field with name starting with _
								$acf_field_object = get_field_object( $custom_field->meta_key, $custom_field->post_id );
								if ( $acf_field_object ) { // this is valid ACF field
									$this->iclTranslationManagement->settings[ 'custom_fields_translation' ][ $custom_field->meta_key ] = $field['wpml_cf_preferences'];
								}
							}
						}
					}
				}
			}
		}
	}

	public function mark_not_migrated_field( $label, $field ) {
		if ( !isset( $field['wpml_cf_preferences'] ) ) {
			$post_exist = $this->get_post_with_custom_field( $field['name'] );
			if ( $post_exist ) {
				$label .= ' <span class="dashicons dashicons-warning acfml-not-migrated"
 							title="' . __("Please review WPML translation preferences for this field before saving field group! Otherwise, default value (Don't translate) will be set.", "acfml") . '"></span>';
			}
		}

		return $label;
	}
}
