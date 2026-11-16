<?php
/**
 * Barcode Source.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources;

/**
 * Class Barcode_Source
 */
class Barcode_Source {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'airwpsync/get_table_fields', array( $this, 'explode_fields' ) );
	}

	/**
	 * Replace each barcode field by two entries: type and value,
	 *
	 * @param array $fields Airtable fields.
	 *
	 * @return array
	 */
	public function explode_fields( $fields ) {
		foreach ( $fields as $i => $field ) {
			if ( in_array( $field->type, array( 'barcode' ), true ) ) {
				$part_one   = array_slice( $fields, 0, $i );
				$part_two   = array_slice( $fields, $i + 1 );
				$new_values = array(
					(object) array(
						'type' => sprintf( '%s.%s', $field->type, 'type' ),
						'id'   => sprintf( '%s.%s', $field->id, 'type' ),
						'name' => sprintf( '%s (%s)', $field->name, __( 'Type', 'air-wp-sync-pro-plus' ) ),
					),
					(object) array(
						'type' => sprintf( '%s.%s', $field->type, 'text' ),
						'id'   => sprintf( '%s.%s', $field->id, 'text' ),
						'name' => sprintf( '%s (%s)', $field->name, __( 'Value', 'air-wp-sync-pro-plus' ) ),
					),
				);
				$fields     = array_merge( $part_one, $new_values, $part_two );
			}
		}
		return $fields;
	}
}
