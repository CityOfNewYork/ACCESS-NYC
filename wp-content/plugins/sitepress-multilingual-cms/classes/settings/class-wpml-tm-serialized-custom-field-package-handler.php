<?php

class WPML_TM_Serialized_Custom_Field_Package_Handler {

	/** @var WPML_Custom_Field_Setting_Factory $custom_field_setting_factory */
	private $custom_field_setting_factory;

	public function __construct( WPML_Custom_Field_Setting_Factory $custom_field_setting_factory ) {
		$this->custom_field_setting_factory = $custom_field_setting_factory;
	}

	public function add_hooks() {
		add_filter(
			'wpml_translation_job_post_meta_value_translated',
			array(
				$this,
				'translate_only_whitelisted_attributes',
			),
			10,
			2
		);

		add_filter(
			'wpml_tm_adjust_translation_fields',
			array(
				$this,
				'set_title_for_whitelisted_attributes',
			)
		);
	}

	/**
	 * @param int    $translated
	 * @param string $custom_field_job_type - e.g: field-my_custom_field-0-my_attribute.
	 *
	 * @return int
	 */
	public function translate_only_whitelisted_attributes( $translated, $custom_field_job_type ) {
		if ( $translated ) {
			list( $custom_field, $attributes ) = WPML_TM_Field_Type_Encoding::decode( $custom_field_job_type );
			if ( $custom_field && $attributes ) {
				$settings             = $this->custom_field_setting_factory->post_meta_setting( $custom_field );
				$attributes_whitelist = $settings->get_attributes_whitelist();

				if ( $attributes_whitelist ) {
					$translated = $this->match_in_order( $attributes, $attributes_whitelist ) ? $translated : 0;
				}
			}
		}

		return $translated;
	}

	/**
	 * Matches the attributes array to the whitelist array
	 * The whitelist array has the attribute as the key to another array for sub keys
	 * eg. array( 'attribute1' => array( 'subkey1' => '' ) )
	 *
	 * @param array $attributes - The attributes in the custom field.
	 * @param array $whitelist - The whitelist attributes to match against.
	 * @param int   $current_depth - The current depth in the attributes array.
	 *
	 * @return bool
	 */
	private function match_in_order( $attributes, $whitelist, $current_depth = 0 ) {
		$current_attribute = $attributes[ $current_depth ];
		$wildcard_match    = $this->match_with_wildcards( $current_attribute, array_keys( $whitelist ) );
		if ( $wildcard_match ) {
			if ( count( $attributes ) === $current_depth + 1 ) {
				return true;
			} else {
				return $this->match_in_order( $attributes, $whitelist[ $wildcard_match ], $current_depth + 1 );
			}
		}

		return false;
	}

	/**
	 * @param array[] $fields
	 *
	 * @return array[]
	 */
	public function set_title_for_whitelisted_attributes( $fields ) {
		foreach ( $fields as $index => $field ) {
			list( $custom_field, $attributes ) = WPML_TM_Field_Type_Encoding::decode( $field['field_type'] );
			if ( $custom_field && $attributes ) {
				$settings             = $this->custom_field_setting_factory->post_meta_setting( $custom_field );
				$attributes_whitelist = $settings->get_attributes_whitelist();

				if ( $attributes_whitelist ) {
					$title = $this->find_title_in_order( $attributes, $attributes_whitelist );
					if ( '' !== $title ) {
						$fields[ $index ]['title'] = $title;
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Matches the attributes array to the whitelist array to find the label
	 * The whitelist array has the attribute nested keys and the value is the label
	 * eg. array( 'attribute1' => array( 'subkey1' => 'Label' ) )
	 *
	 * @param array $attributes    The attributes in the custom field.
	 * @param array $whitelist     The whitelist attributes to match against.
	 * @param int   $current_depth The current depth in the attributes array.
	 *
	 * @return string
	 */
	private function find_title_in_order( $attributes, $whitelist, $current_depth = 0 ) {
		$current_attribute = $attributes[ $current_depth ];
		$wildcard_match    = $this->match_with_wildcards( $current_attribute, array_keys( $whitelist ) );
		if ( $wildcard_match ) {
			if ( count( $attributes ) === $current_depth + 1 ) {
				return $whitelist[ $current_attribute ] ?? '';
			} else {
				return $this->find_title_in_order( $attributes, $whitelist[ $wildcard_match ], $current_depth + 1 );
			}
		}

		return '';
	}

	/**
	 * Matches the attribute to the whitelist array using wildcards.
	 * Wildcards can only be used at the end of the string.
	 * eg. 'title-*', 'data*', '*'
	 * A '*' matches everything.
	 *
	 * @param string $attribute - the current attributes.
	 * @param array  $whitelist - the whitelist to match against.
	 *
	 * @return string - Returns the whitelist string match.
	 */
	private function match_with_wildcards( $attribute, $whitelist ) {
		foreach ( $whitelist as $white_value ) {
			$asterisk_pos = strpos( $white_value, '*' );
			if ( false === $asterisk_pos ) {
				if ( $attribute === $white_value ) {
					return $white_value;
				}
			} else {
				if (
					0 === $asterisk_pos ||
					substr( $attribute, 0, $asterisk_pos ) === substr( $white_value, 0, $asterisk_pos )
				) {
					return $white_value;
				}
			}
		}

		return '';
	}
}
