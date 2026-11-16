<?php
/**
 * Abstract Destination.
 * Base class to define import destination.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Destinations;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;

/**
 * Class Abstract_Destination
 */
abstract class Abstract_Destination {
	/**
	 * Destination slug
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Module slug
	 *
	 * @var string
	 */
	protected $module;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'airwpsync/get_wp_fields', array( $this, 'add_fields' ), 10, 2 );
	}

	/**
	 * Assign fields to mapping group
	 *
	 * @return array
	 */
	abstract protected function get_group();

	/**
	 * Get mapping fields
	 *
	 * @return array
	 */
	abstract protected function get_mapping_fields();

	/**
	 * Add fields to mapping options
	 *
	 * @param array  $fields Fields.
	 * @param string $module Module's slug.
	 *
	 * @return array|mixed
	 */
	public function add_fields( $fields, $module ) {
		if ( $module === $this->module ) {
			$group          = $this->get_group();
			$new_fields     = $this->get_mapping_fields();
			$options        = array();
			$default_option = array(
				'enabled'        => true,
				'allow_multiple' => false,
			);
			foreach ( $new_fields as $field ) {
				$options[] = array_merge( $default_option, $field, array( 'value' => $this->slug . '::' . $field['value'] ) );
			}

			$fields = array_merge_recursive(
				$fields,
				array(
					$group['slug'] => array(
						'options' => $options,
					),
				)
			);
			if ( ! empty( $group['label'] ) ) {
				$fields[ $group['slug'] ]['label'] = $group['label'];
			}
		}
		return $fields;
	}

	/**
	 * Get mapped fields for our destination specifically
	 *
	 * @param Abstract_Importer $importer Importer.
	 *
	 * @return array
	 */
	protected function get_destination_mapping( $importer ) {
		$mapping             = ! empty( $importer->config()->get( 'mapping' ) ) ? $importer->config()->get( 'mapping' ) : array();
		$destination_mapping = array();
		$module              = $importer->config()->get( 'module' );

		if ( $module !== $this->module ) {
			return $destination_mapping;
		}

		foreach ( $mapping as $mapping_pair ) {
			$wp_field_parts = explode( '::', $mapping_pair['wordpress'] );
			$wp_field_group = $wp_field_parts[0] ? $wp_field_parts[0] : '';
			$wp_field       = $wp_field_parts[1] ? $wp_field_parts[1] : '';

			if ( $wp_field_group === $this->slug ) {
				$destination_mapping[] = array_merge( $mapping_pair, array( 'wordpress' => $wp_field ) );
			}
		}
		return $destination_mapping;
	}

	/**
	 * Get source type from remote id
	 *
	 * @param string            $remote_id Remote field id.
	 * @param Abstract_Importer $importer Importer.
	 *
	 * @return mixed|string
	 */
	protected function get_source_type( $remote_id, $importer ) {
		foreach ( $importer->get_remote_fields() as $field ) {
			if ( $field->id === $remote_id ) {
				// @TODO: move this bit, it's specific to Airtable.
				// Use result type for computed fields (formula, lookup).
				return ! empty( $field->options->result->type ) ? $field->options->result->type : $field->type;
			}
		}
		return '';
	}

	/**
	 * Get remote field definition by id
	 *
	 * @param string            $remote_id Remote field id.
	 * @param Abstract_Importer $importer Importer.
	 */
	protected function get_field_by_id( $remote_id, $importer ) {
		foreach ( $importer->get_remote_fields() as $field ) {
			if ( $field->id === $remote_id ) {
				return $field;
			}
		}
		return '';
	}

	/**
	 * Get remote value
	 *
	 * @param array             $fields Fields.
	 * @param string            $remote_id Remote field id.
	 * @param Abstract_Importer $importer Importer.
	 *
	 * @return mixed|string
	 */
	protected function get_remote_value( $fields, $remote_id, $importer ) {
		$source_type = $this->get_source_type( $remote_id, $importer );
		// Get real field id if needed.
		if ( preg_match( '/(.+)\.(.+)/', $remote_id, $matches ) ) {
			$remote_id = $matches[1];
		}

		$value = array_key_exists( $remote_id, $fields ) ? $fields[ $remote_id ] : '';

		// Handle object properties.
		if ( preg_match( '/(.+)\.(.+)/', $source_type, $matches ) ) {
			$key = $matches[2];

			if ( is_array( $value ) ) {
				foreach ( $value as &$val ) {
					$val = is_object( $val ) ? $val->{$key} : '';
				}
			} else {
				$value = is_object( $value ) ? $value->{$key} : '';
			}
		}
		return $value;
	}


	/**
	 * Return field mapping based on the field id.
	 *
	 * @param string $field_id The field id.
	 *
	 * @return array|null
	 */
	public function get_field_mapping( $field_id ) {
		$fields        = $this->get_mapping_fields();
		$field_mapping = null;
		foreach ( $fields as $field ) {
			if ( $field['value'] === $field_id ) {
				$field_mapping = $field;
				break;
			}
		}
		return $field_mapping;
	}
}
