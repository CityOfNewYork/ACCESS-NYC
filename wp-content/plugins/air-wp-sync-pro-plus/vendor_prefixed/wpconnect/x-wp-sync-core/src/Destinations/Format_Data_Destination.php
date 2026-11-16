<?php
/**
 * Trait Format data destination.
 * Allows to migrate one by one destinations class to strategy formatters.
 * Requires the formatter property to be defined in the destination class.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Context;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Trait Format_Data_Destination
 */
trait Format_Data_Destination {

	/**
	 * Format data: for each mapped field filtered by $filter_fields run formatting strategies defined in $strategies_conf or use $default_format_strategies if there is no configuration for the field.
	 *
	 * @param array          $data Data for format (associative array wp field key => value).
	 * @param Import_Context $context Import context.
	 * @param array          $fields Fields.
	 * @param array          $strategies_conf {
	 *               A list of custom strategies.
	 *      @type callable $pre_format Optional: A function to pre-format the value before applying formatting strategies on it. Accepts 1 or 2 parameters: $value & $context.
	 *      @type string[] $strategies A list of strategies’ slugs.
	 *      @type callable $pre_format Optional: A function to post-format the value after the formatting strategies has been applied on it. One parameter: $value.
	 * }
	 * @param array          $filter_fields List of WordPress field key to apply format on. Empty array = all fields.
	 * @param array          $default_format_strategies List of default format strategies to apply on values without custom strategies.
	 *
	 * @return mixed
	 * @throws \ReflectionException Exception regarding the reflection.
	 */
	protected function format_data( $data, $context, $fields, $strategies_conf, $filter_fields = array(), $default_format_strategies = array( 'format_to_string' ) ) {
		$importer      = $context->get_importer();
		$mapped_fields = $this->get_destination_mapping( $importer );

		foreach ( $mapped_fields as $mapped_field ) {
			$wordpress_field_key = $mapped_field['wordpress'];
			if ( ! empty( $filter_fields ) && ! in_array( $wordpress_field_key, $filter_fields, true ) ) {
				continue;
			}

			$source_field  = $this->get_field_by_id( $mapped_field['airtable'], $importer );
			$field_context = Import_Field_Context::from_import_context( $context );
			$field_context->set_source_field( ! empty( $source_field ) ? $source_field : null );
			$field_context->set_destination_field_mapping( $this->get_field_mapping( $wordpress_field_key ) );
			$field_context->set_destination_options( $mapped_field['options'] ?? array() );

			$value                   = $this->get_remote_value( $fields, $mapped_field['airtable'], $importer );
			$field_format_strategies = array( 'strategies' => $default_format_strategies );

			if ( isset( $strategies_conf[ $wordpress_field_key ] ) ) {
				$field_format_strategies = $strategies_conf[ $wordpress_field_key ];
			}
			if ( isset( $field_format_strategies['pre_format'] ) ) {
				$rf = new \ReflectionFunction( $field_format_strategies['pre_format'] );
				if ( $rf->getNumberOfRequiredParameters() === 1 ) {
					$value = $field_format_strategies['pre_format']( $value );
				} elseif ( $rf->getNumberOfRequiredParameters() === 2 ) {
					$value = $field_format_strategies['pre_format']( $value, $field_context );
				}
			}
			foreach ( $field_format_strategies['strategies'] as $field_format_strategy ) {
				$value = $this->formatter->format( $field_format_strategy, $value, $field_context );
			}
			if ( isset( $field_format_strategies['post_format'] ) ) {
				$value = $field_format_strategies['post_format']( $value );
			}
			if ( ! empty( $mapped_field['options']['name'] ) ) {
				$wordpress_field_key = $mapped_field['options']['name'];
			}
			$data[ $wordpress_field_key ] = $value;
		}

		return $data;
	}
}
