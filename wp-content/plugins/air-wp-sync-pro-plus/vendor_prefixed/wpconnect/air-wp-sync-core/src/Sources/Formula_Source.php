<?php
/**
 * Formula Source.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources;

/**
 * Class Formula_Source
 */
class Formula_Source {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'airwpsync/get_table_fields', array( $this, 'convert_formula_fields' ) );
	}

	/**
	 * Replace each formula field by its counterpart defined by its return value type.
	 *
	 * @param array $fields Airtable fields.
	 *
	 * @return array
	 */
	public function convert_formula_fields( $fields ) {
		$fields = array_map(
			function ( $field ) {
				if ( 'formula' === $field->type ) {
					if ( isset( $field->options->result->type ) ) {
						// Copy formula result as a new field.
						$new_field = clone $field->options->result;
						// Keep id, name and description from the original formula field.
						$new_field->id          = $field->id;
						$new_field->name        = $field->name;
						$new_field->description = $field->description ?? '';
						return $new_field;
					}
				}
				return $field;
			},
			$fields
		);
		return $fields;
	}
}
