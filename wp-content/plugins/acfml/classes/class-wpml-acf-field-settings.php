<?php

use ACFML\FieldGroup\Mode;
use ACFML\Helper\FieldGroup;
use WPML\FP\Obj;

class WPML_ACF_Field_Settings implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/**
	 * @var array Translation Managament settings indexes which should be updated.
	 */
	private $tm_setting_index = [ 'custom_fields_translation', 'custom_term_fields_translation' ];

	/**
	 * @var TranslationManagement TranslationManagement object.
	 */
	private $translation_management;

	/**
	 * @var bool
	 */
	private $new_preference_set = false;

	/**
	 * WPML_ACF_Field_Settings constructor.
	 *
	 * @param TranslationManagement $translation_management TranslationManagement object.
	 */
	public function __construct( TranslationManagement $translation_management ) {
		$this->translation_management = $translation_management;
	}

	/**
	 * Register WordPress hooks related to ACF field settings.
	 */
	public function add_hooks() {
		// add radio buttons on Field Group page.
		add_action( 'acf/render_field_settings', [ $this, 'render_field_settings' ], 10, 1 );

		// handle setting sync preferences on Field Group page.
		add_filter( 'acf/update_field', [ $this, 'update_field_settings' ], 10, 1 );

		// when user adds new field value on post edit screen.
		add_filter( 'acf/update_value', [ $this, 'field_value_updated' ], 10, 4 );

		// use case when user updates sync prefernces on post edit screen.
		add_action( 'wpml_single_custom_field_sync_option_updated', [ $this, 'user_set_sync_preferences' ], 10, 1 );
		add_action( 'wpml_custom_fields_sync_option_updated', [ $this, 'user_set_sync_preferences' ], 10, 1 );

		// mark field as not migrated yet.
		add_filter( 'acf/get_field_label', [ $this, 'mark_not_migrated_field' ], 10, 2 );
	}

	/**
	 * Adds new row to ACF field configuration screen.
	 *
	 * Adds options to set translation prefreences for the field. If the field should
	 * be always copied, does not add anything.
	 *
	 * @param array $field ACF field array.
	 */
	public function render_field_settings( $field ) {
		acf_render_field_setting(
			$field,
			[
				'label'         => __( 'Translation preferences', 'acfml' ),
				'instructions'  => __( 'What to do with field\'s value when post/page is going to be translated', 'acfml' ),
				'type'          => 'radio',
				'name'          => 'wpml_cf_preferences',
				'layout'        => 'horizontal',
				'choices'       => $this->getFieldOptions(),
			],
			true
		);
	}

	public function update_field_settings( $field ) {

		if ( $this->is_field_parsable( $field ) ) {
			$this->update_existing_subfields( $field );
			$this->save_field_settings( $field );
		}

		return $field;
	}

	/**
	 * Synchronise translation preferences when user adds new field value on post edit screen.
	 *
	 * @param mixed $value   Field value being updated.
	 * @param int   $post_id The ID of current post being updated.
	 * @param array $field   The ACF field.
	 * @param null  $_value  Deprecated.
	 *
	 * @return mixed
	 */
	public function field_value_updated( $value, $post_id, $field, $_value = null ) {
		if ( $this->is_field_parsable( $field ) ) {
			$this->save_field_settings( $field );
		}

		return $value;
	}

	/**
	 * Checks if field has all required elements or is the field which always should be set to copy.
	 *
	 * @param array $field ACF field data.
	 *
	 * @return bool
	 */
	private function is_field_parsable( $field ) {
		return ( isset( $field['wpml_cf_preferences'], $field['name'] ) && $this->isValidFieldPreference( $field['wpml_cf_preferences'] ) && $field['name'] )
			|| $this->field_should_be_set_to_copy_once( $field );
	}

	/**
	 * Get array of field preference numeric values with displayed descriptions.
	 *
	 * @return array
	 */
	private function getFieldOptions() {
		return [
			WPML_IGNORE_CUSTOM_FIELD    => __( "Don't translate", 'acfml' ),
			WPML_COPY_CUSTOM_FIELD      => __( 'Copy', 'acfml' ),
			WPML_COPY_ONCE_CUSTOM_FIELD => __( 'Copy once', 'acfml' ),
			WPML_TRANSLATE_CUSTOM_FIELD => __( 'Translate', 'acfml' ),
		];
	}

	/**
	 * Checks if preference is being about to set has a valid value.
	 *
	 * @param int $preference
	 *
	 * @return bool
	 */
	private function isValidFieldPreference( $preference ) {
		return array_key_exists( $preference, $this->getFieldOptions() );
	}

	/**
	 * Save translation preferences for custom field in Translation Management settings.
	 *
	 * @param array $field The ACF field being updated.
	 */
	private function save_field_settings( $field ) {
		if ( isset( $field['wpml_cf_preferences'] ) ) {
			foreach ( $this->tm_setting_index as $setting_index ) {
				$this->maybe_set_new_preference( $setting_index, $field['name'], $field['wpml_cf_preferences'] );
			}
			if ( WPML_IGNORE_CUSTOM_FIELD !== (int) $field['wpml_cf_preferences'] ) {
				$this->update_corresponding_system_field_settings( $field, $field['name'] );
			}
			if ( $this->new_preference_set ) {
				$this->translation_management->save_settings();
			}
		}
	}

	/**
	 * Synchronises custom field translation preferences saved on post edit screen.
	 *
	 * @param array $cft Custom fields translation preferences.
	 */
	public function user_set_sync_preferences( $cft ) {

		foreach ( $cft as $field_name => $field_preferences ) {
			$post_id      = $this->get_post_with_custom_field( $field_name );
			$field_object = get_field_object( $field_name, $post_id );

			if ( $this->is_field_object_valid( $field_object ) ) {
				if ( $field_object['wpml_cf_preferences'] !== $field_preferences ) {
					$this->update_field_group_post( $field_object['ID'], $field_preferences );
				}
			}
		}

		// this action runs also for case 'icl_tcf_translation', @see \TranslationManagement::ajax_calls
		// it shouldn't because it will overwrite normal cf fields values with zeros.
		remove_action( 'wpml_custom_fields_sync_option_updated', [ $this, 'user_set_sync_preferences' ], 10 );
	}

	/**
	 * Set translation preference for field group post in wp_posts table.
	 *
	 * @param int $field_object_id   Id of the field group which has to be updated.
	 * @param int $field_preferences Translation preference to set.
	 */
	public function update_field_group_post( $field_object_id, $field_preferences ) {
		if ( ! $this->isValidFieldPreference( $field_preferences ) ) {
			return;
		}
		$field_post = get_post( $field_object_id );
		if ( is_object( $field_post ) ) {
			$field_post_content = maybe_unserialize( $field_post->post_content );
			if ( is_array( $field_post_content ) ) {
				$field_post_content['wpml_cf_preferences'] = $field_preferences;
				wp_update_post(
					[
						'ID'           => $field_object_id,
						'post_content' => maybe_serialize( $field_post_content ),
					]
				);
			}
		}
	}

	/**
	 * Validates ACF field object.
	 *
	 * @param array $field_object The ACF field object.
	 *
	 * @return bool
	 */
	private function is_field_object_valid( $field_object ) {
		return is_array( $field_object ) && is_numeric( $field_object['ID'] ) && $field_object['ID'] > 0;
	}

	/**
	 * Get ID of post having given ACF field.
	 *
	 * @param string $field_name ACF field name.
	 *
	 * @return object|string|void|null
	 */
	private function get_post_with_custom_field( $field_name ) {
		$post_id = get_the_ID() ?: get_queried_object();
		if ( ! is_numeric( $post_id ) ) {
			global $wpdb;
			$query   = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1";
			$post_id = $wpdb->get_var( $wpdb->prepare( $query, $field_name ) );
		}
		return $post_id;
	}

	/**
	 * Find current field subfields and udpate their translation preferences.
	 *
	 * @param array $field The ACF field.
	 */
	private function update_existing_subfields( $field ) {
		if ( isset( $field['parent'] ) ) {
			$parent_post_type = get_post_type( $field['parent'] );
			if ( 'acf-field' === $parent_post_type ) { // yes, it is subfield.
				global $wpdb;
				$query          = "SELECT * FROM {$wpdb->postmeta} WHERE meta_key LIKE %s";
				$prepared       = $wpdb->prepare( $query, '%' . $wpdb->esc_like( $field['name'] ) );
				$query_result   = $wpdb->get_results( $prepared ); // all custom fields from postmeta created on base of this ACF subfield.
				$handled_fields = [];
				if ( is_array( $query_result ) && ! empty( $query_result ) ) {
					foreach ( $query_result as $custom_field ) {
						if ( ! in_array( $custom_field->meta_key, $handled_fields ) ) {
							$handled_fields[] = $custom_field->meta_key;
							if ( substr( $custom_field->meta_key, 0, 1 ) !== '_' ) { // this is not a field with name starting with.
								$acf_field_object = get_field_object( $custom_field->meta_key, $custom_field->post_id );
								if ( $acf_field_object ) { // this is valid ACF field.
									foreach ( $this->tm_setting_index as $setting_index ) {
										$this->maybe_set_new_preference( $setting_index, $custom_field->meta_key, $field['wpml_cf_preferences'] );
									}
									if ( WPML_IGNORE_CUSTOM_FIELD !== (int) $field['wpml_cf_preferences'] ) {
										$this->update_corresponding_system_field_settings( $acf_field_object, $custom_field->meta_key );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Update corresponding system field's translation preferences to "Copy" or "Copy once".
	 *
	 * Corresponding system fields' names starts with underscore.
	 * They should be set to "Copy once" when the field group is on Localization mode.
	 *
	 * @param array  $field      The ACF field array.
	 * @param string $field_name Current field meta_key.
	 *
	 * @return void
	 */
	private function update_corresponding_system_field_settings( $field, $field_name ) {
		$fieldGroupId = FieldGroup::getId( Obj::prop( 'parent', $field ) );
		if ( $fieldGroupId ) {
			$preference = Mode::LOCALIZATION === Mode::getMode( acf_get_field_group( $fieldGroupId ) ) ? WPML_COPY_ONCE_CUSTOM_FIELD : WPML_COPY_CUSTOM_FIELD;

			$corresponding_field_name = '_' . $field_name;
			foreach ( $this->tm_setting_index as $setting_index ) {
				$this->maybe_set_new_preference( $setting_index, $corresponding_field_name, $preference );
			}
		}
	}

	/**
	 * Adds excalamtion mark with title to the field which translation preferences hasn't been set yet.
	 *
	 * @param string $label ACF field's label.
	 * @param array  $field ACF field's metadata.
	 *
	 * @return string Field's label updated with exclamation mark.
	 */
	public function mark_not_migrated_field( $label, $field ) {
		if ( ! isset( $field['wpml_cf_preferences'] ) && $field['ID'] > 0 && $this->isFieldGroupEditScreen() ) {
			$post_exist = $this->get_post_with_custom_field( $field['name'] );
			if ( $post_exist ) {
				$label .= sprintf(
					' <i class="otgs-ico-warning-o js-otgs-popover-tooltip"  data-tippy-zIndex="999999" title="%s"></i>',
					esc_attr__( 'Edit the field to set the translation preference.', 'acfml' )
				);
			}
		}

		return $label;
	}

	/**
	 * Checks if it is currently displayed ACF Field Group edit screen.
	 *
	 * @return bool
	 */
	private function isFieldGroupEditScreen() {
		global $post_type, $editing;
		return 'acf-field-group' === $post_type && true === $editing;
	}

	/**
	 * Check if fields type is among fields which always chouls be set to copy.
	 *
	 * @param array $field ACF field data.
	 *
	 * @return bool
	 */
	public function field_should_be_set_to_copy_once( $field ) {
		$fields_always_copied = [
			'repeater',
			'flexible_content',
		];
		return isset( $field['type'] ) && in_array( $field['type'], $fields_always_copied, true );
	}

	/**
	 * Checks if fields translation preferences has already been migrated.
	 *
	 * @param array $field Field array.
	 *
	 * @return bool
	 */
	public function fieldPreferencesNotMigrated( $field ) {
		return isset( $field['wpml_cf_preferences'] )
				&& WPML_IGNORE_CUSTOM_FIELD === $field['wpml_cf_preferences'];
	}

	private function maybe_set_new_preference( $setting_index, $field, $preference ) {
		if ( ! isset( $this->translation_management->settings[ $setting_index ][ $field ] )
		     || $this->translation_management->settings[ $setting_index ][ $field ] !== $preference
		) {
			$this->translation_management->settings[ $setting_index ][ $field ] = $preference;
			$this->new_preference_set = true;
		}
	}
}
