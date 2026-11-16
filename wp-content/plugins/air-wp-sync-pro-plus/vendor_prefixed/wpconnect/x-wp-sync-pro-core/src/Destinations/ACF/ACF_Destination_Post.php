<?php
/**
 * Manages import as ACF field on posts.
 *
 * @package wpconnect/x-wp-sync-pro-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Pro_Core\Destinations\ACF;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;

/**
 * ACF_Destination_Post class.
 */
class ACF_Destination_Post extends Abstract_ACF_Destination {

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $module = 'post';

	/**
	 * Formatter.
	 *
	 * @var Abstract_Formatter
	 */
	protected $formatter;

	/**
	 * Constructor
	 *
	 * @param Abstract_Formatter $formatter Formatter.
	 */
	public function __construct( $formatter ) {
		parent::__construct( $formatter );

		// Add "attach_to_post_id" arg for fields that use "format_attachments_to_attachments_id" formatter strategy.
		foreach ( $this->acf_to_local_types as $acf_key => $destination_config ) {
			if ( ! isset( $destination_config['format'] ) || ( is_array( $destination_config['format'] ) && ! in_array( 'format_attachments_to_attachments_id', $destination_config['format'], true ) ) ) {
				continue;
			}
			$old_pre_format_function                            = $this->acf_to_local_types[ $acf_key ]['pre_format'] ?? function ( $value ) {
				return $value;
			};
			$this->acf_to_local_types[ $acf_key ]['pre_format'] = function ( $value, $context ) use ( $old_pre_format_function ) {
				$context->set_args( array( 'attach_to_post_id' => $context->get_existing_object_id() ) );
				return $old_pre_format_function( $value, $context );
			};
		}

		add_filter( 'airwpsync/features_by_post_type', array( $this, 'add_features_by_post_type' ), 10, 2 );
	}

	/**
	 * Update post's ACF field.
	 *
	 * @param string $key Field key.
	 * @param mixed  $value Field valye.
	 * @param int    $destination_id WordPress destination object id.
	 *
	 * @return void
	 */
	public function update_field( $key, $value, $destination_id ) {
		update_field( $key, $value, $destination_id );
	}

	/**
	 * Add field features for each post types
	 *
	 * @param array  $features Features list.
	 * @param string $post_type Post type.
	 *
	 * @return string[]
	 */
	public function add_features_by_post_type( $features, $post_type ) {
		$destination_features = array();

		$fields = $this->get_acf_fields( array( 'post_type' => $post_type ) );
		foreach ( $fields as $field ) {
			$destination_features[] = $field['group']['key'] . '.' . $field['key'];
		}

		$features[ $this->slug ] = $destination_features;

		return $features;
	}

	/**
	 * Change filter logic to include all groups with at least one rule about the post_type in the filters.
	 *
	 * @param array $filters Filters passed to `acf_get_field_groups`.
	 *
	 * @return array
	 */
	protected function acf_get_field_groups( $filters = array() ) {
		if ( isset( $filters['post_type'] ) ) {
			$groups = acf_get_field_groups( array() );
			$args   = $filters;
			$groups = array_filter(
				$groups,
				function ( $field_group ) use ( $args ) {
					// Check if active.
					if ( ! $field_group['active'] ) {
						return false;
					}

					// Check if location rules exist.
					if ( $field_group['location'] ) {

						// Get the current screen.
						$screen = acf_get_location_screen( $args );

						// Loop through location groups.
						foreach ( $field_group['location'] as $group ) {

							// ignore group if no rules.
							if ( empty( $group ) ) {
								continue;
							}

							// Loop over rules and determine if at least one rule match.
							$match_group = false;
							foreach ( $group as $rule ) {
								if ( acf_match_location_rule( $rule, $screen, $field_group ) ) {
									$match_group = true;
									break;
								}
							}

							// If this group matches, show the field group.
							if ( $match_group ) {
								return true;
							}
						}
					}

					// Default.
					return false;
				}
			);

			return $groups;
		} else {
			return parent::acf_get_field_groups( $filters );
		}
	}
}
