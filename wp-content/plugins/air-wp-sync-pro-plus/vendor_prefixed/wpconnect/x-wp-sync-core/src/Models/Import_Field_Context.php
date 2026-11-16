<?php
/**
 * Import field context.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models;

use Exception;

/**
 * Import_Field_Context class
 */
class Import_Field_Context extends Import_Context {

	/**
	 * Source field.
	 *
	 * @var \stdClass|null
	 */
	protected $source_field = null;

	/**
	 * Source field type.
	 *
	 * @var string|null
	 */
	protected $source_type = null;

	/**
	 * WordPress destination field mapping.
	 *
	 * @see Abstract_Destination::get_mapping_fields && Abstract_Destination::get_field_mapping).
	 *
	 * @var array|null
	 */
	protected $destination_field_mapping = null;

	/**
	 * WordPress destination field options.
	 *
	 * @var array
	 */
	protected $destination_options = array();

	/**
	 * Field args.
	 *
	 * @var array
	 */
	protected $args = array();


	/**
	 * Get source field.
	 *
	 * @return \stdClass|null
	 */
	public function get_source_field() {
		return $this->source_field;
	}

	/**
	 * Set source field.
	 *
	 * @param \stdClass|null $source_field Source field.
	 */
	public function set_source_field( $source_field ) {
		$this->source_field = $source_field;
		return $this;
	}

	/**
	 * Returns source type.
	 *
	 * @return string|mixed
	 */
	public function get_source_type() {
		// @TODO: exclude this bit from this class (specific to Airtable).
		// Use result type for computed fields (formula, lookup).
		return $this->source_field && ! empty( $this->source_field->options->result->type ) ? $this->source_field->options->result->type : $this->source_field->type;
	}

	/**
	 * Get destination field mapping.
	 *
	 * @return array|null
	 */
	public function get_destination_field_mapping() {
		return $this->destination_field_mapping;
	}

	/**
	 * Set destination field mapping.
	 *
	 * @param array|null $destination_field_mapping Destination field mapping.
	 */
	public function set_destination_field_mapping( $destination_field_mapping ) {
		$this->destination_field_mapping = $destination_field_mapping;
	}

	/**
	 * Returns source type.
	 *
	 * @return array
	 */
	public function get_args(): array {
		return $this->args;
	}

	/**
	 * Set field import args.
	 *
	 * @param array $args Args.
	 *
	 * @return void
	 */
	public function set_args( array $args ) {
		$this->args = $args;
	}

	/**
	 * Get field import arg value.
	 *
	 * @param string $arg_name Field import arg name.
	 *
	 * @return mixed
	 */
	public function get_arg( $arg_name ) {
		return $this->args[ $arg_name ];
	}

	/**
	 * Returns true if the arg with the name $arg_name exists.
	 *
	 * @param string $arg_name Field import arg name.
	 *
	 * @return bool
	 */
	public function has_arg( $arg_name ) {
		return isset( $this->args[ $arg_name ] );
	}

	/**
	 * Builds import field context from import context.
	 *
	 * @param Import_Context $import_context Import context.
	 *
	 * @return Import_Field_Context
	 */
	public static function from_import_context( $import_context ) {
		$import_field_context = new Import_Field_Context();
		$import_field_context->set_importer( $import_context->get_importer() );
		$import_field_context->set_existing_object_id( $import_context->get_existing_object_id() );
		return $import_field_context;
	}

	/**
	 * Get destination field option.
	 *
	 * @return array
	 */
	public function get_destination_options(): array {
		return $this->destination_options;
	}

	/**
	 * Set destination field option.
	 *
	 * @param array $destination_options Destination field options.
	 */
	public function set_destination_options( array $destination_options ) {
		$this->destination_options = $destination_options;
	}
}
