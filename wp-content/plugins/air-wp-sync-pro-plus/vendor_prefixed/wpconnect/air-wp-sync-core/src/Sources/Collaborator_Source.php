<?php
/**
 * Collaborator Source.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Sources;

/**
 * Class Collaborator_Source
 */
class Collaborator_Source {
	/**
	 * Collaborator's fields types.
	 *
	 * @var string[]
	 */
	protected $fields = array(
		'singleCollaborator',
		'multipleCollaborators',
		'createdBy',
		'lastModifiedBy',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'airwpsync/get_table_fields', array( $this, 'explode_fields' ) );
	}

	/**
	 * Replace each collaborator field by three entries: ID, Email and Name,
	 *
	 * @param array $fields Airtable fields.
	 *
	 * @return array
	 */
	public function explode_fields( $fields ) {
		$offset = 0;
		foreach ( $fields as $i => $field ) {
			if ( in_array( $field->type, $this->fields, true ) ) {
				$actual_i   = $i + $offset;
				$part_one   = array_slice( $fields, 0, $actual_i );
				$part_two   = array_slice( $fields, $actual_i + 1 );
				$new_values = array(
					(object) array(
						'type' => sprintf( '%s.%s', $field->type, 'id' ),
						'id'   => sprintf( '%s.%s', $field->id, 'id' ),
						'name' => sprintf( '%s (%s)', $field->name, __( 'ID', 'air-wp-sync-pro-plus' ) ),
					),
					(object) array(
						'type' => sprintf( '%s.%s', $field->type, 'email' ),
						'id'   => sprintf( '%s.%s', $field->id, 'email' ),
						'name' => sprintf( '%s (%s)', $field->name, __( 'Email', 'air-wp-sync-pro-plus' ) ),
					),
					(object) array(
						'type' => sprintf( '%s.%s', $field->type, 'name' ),
						'id'   => sprintf( '%s.%s', $field->id, 'name' ),
						'name' => sprintf( '%s (%s)', $field->name, __( 'Name', 'air-wp-sync-pro-plus' ) ),
					),
				);
				$fields  = array_merge( $part_one, $new_values, $part_two );
				$offset += count( $new_values ) - 1;
			}
		}
		return $fields;
	}
}
