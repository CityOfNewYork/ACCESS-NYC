<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Compatibility;
use GatherContent\Importer\Sync\Pull;
use GatherContent\Importer\Sync\Push;
use GatherContent\Importer\Base;

/**
 * Handles adding Compatibility for Advanced Custom Fields (ACF)
 *
 * @todo Write transforms for more (all?) types, including possibly flexible content fields.
 *
 * @since 3.1.5
 */
class ACF extends Base {

	protected $gc_acf_type_map = array(
		'choice_checkbox' => 'checkbox',
	);

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.1.5
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_filter( 'gc_new_wp_post_data', array( $this, 'maybe_transform_meta_for_acf' ), 10, 2 );
		add_filter( 'gc_update_wp_post_data', array( $this, 'maybe_transform_meta_for_acf' ), 10, 2 );
		add_filter( 'gc_config_pre_meta_field_value_updated', array( $this, 'maybe_transform_config_meta_from_acf' ), 10, 4 );
	}

	/**
	 * Handles transforming certain meta values from GC to ACF.
	 *
	 * @since  3.1.5
	 *
	 * @param  array $post_data The post data to import/update.
	 * @param  Pull  $pull      The Pull object.
	 *
	 * @return array            The possibly modified post data array.
	 */
	public function maybe_transform_meta_for_acf( $post_data, Pull $pull ) {
		if ( empty( $post_data['meta_input'] ) ) {
			return $post_data;
		}

		$acfs = $this->get_acfs(
			$post_data['post_type'],
			! empty( $post_data['ID'] ) ? $post_data['ID'] : 0
		);

		if ( empty( $acfs ) ) {
			return $post_data;
		}

		foreach ( $acfs as $acf ) {
			$fields = $this->get_acf_fields( $acf );
			if ( ! $fields ) {
				continue;
			}

			foreach ( $fields as $field ) {
				if ( ! isset(
					$field['type'],
					$field['name'],
					$post_data['meta_input'][ $field['name'] ]
				) ) {
					continue;
				}

				$cb = $this->has_pull_transform_method( $field['type'] );

				if ( ! $cb ) {
					continue;
				}

				$meta_value = $cb( $post_data['meta_input'][ $field['name'] ], $field );

				$post_data['meta_input'][ $field['name'] ] = apply_filters( 'gc_transform_meta_for_acf', $meta_value, $field, $post_data, $pull );
			}
		}

		return $post_data;
	}

	/**
	 * Handles transforming certain meta values from ACF to GC.
	 *
	 * @since  3.1.5
	 *
	 * @param  bool   $updated    Whether config element was updated.
	 * @param  mixed  $meta_value The meta value to transform.
	 * @param  string $meta_key   The meta key to transform.
	 * @param  Push   $push       The Push object.
	 *
	 * @return bool               Whether the config element is updated.
	 */
	public function maybe_transform_config_meta_from_acf( $updated, $meta_value, $meta_key, Push $push ) {
		if ( ! isset( $push->element->type, $this->gc_acf_type_map[ $push->element->type ] ) || empty( $meta_value ) ) {
			return $updated;
		}

		$acf_type = $this->gc_acf_type_map[ $push->element->type ];

		$acfs = $this->get_acfs(
			$push->post->post_type,
			$push->post->ID
		);

		if ( empty( $acfs ) ) {
			return $updated;
		}

		foreach ( $acfs as $acf ) {
			$fields = $this->get_acf_fields( $acf );
			if ( ! $fields ) {
				continue;
			}

			foreach ( $fields as $field ) {
				if (
					! isset( $field['type'], $field['name'] )
					|| $acf_type !== $field['type']
				) {
					continue;
				}

				$cb = $this->has_push_transform_method( $field['type'] );

				if ( ! $cb ) {
					continue;
				}

				$updated = $cb( $meta_value, $push, $field );
				break 2;
					// $updated = $this->maybe_transform_checkbox_push_value( func_get_args() );
			}
		}

		return $updated;
	}

	/**
	 * Maybe transform checkbox value to ACF compatible version.
	 *
	 * @since  3.1.5
	 *
	 * @param  mixed  $meta_value GC Checkbox Field value
	 * @param  array  $field      ACF Field array
	 *
	 * @return mixed              Possibly modified meta value.
	 */
	public function maybe_transform_pull_value_checkbox( $meta_value, $field ) {
		if (
			! empty( $meta_value )
			&& is_array( $meta_value )
			&& ! empty( $field['choices'] )
		) {
			$meta_value = array_map( function( $meta_arr_value ) use ( $field ) {

				// Replace choice with the choice key from ACF.
				$key = array_search( $meta_arr_value, $field['choices'] );
				if ( false !== $key ) {
					$meta_arr_value = $key;
				}

				return $meta_arr_value;

			}, $meta_value );
		}

		return $meta_value;
	}

	/**
	 * Maybe transform checkbox value to GC compatible version.
	 *
	 * @since  3.1.5
	 *
	 * @param  mixed  $meta_value GC Checkbox Field value
	 * @param  Push   $push       The Push object.
	 * @param  array  $field      ACF Field array
	 *
	 * @return mixed              Possibly modified meta value.
	 */
	public function maybe_transform_push_value_checkbox( $meta_value, $push, $field ) {
		$updated = false;
		if ( empty( $field['choices'] ) ) {
			return $updated;
		}

		if ( empty( $meta_value ) ) {
			$meta_value = array();
		} else {
			$meta_value = is_array( $meta_value ) ? $meta_value : array( $meta_value );
		}

		foreach ( $meta_value as $key => $value ) {
			$meta_value[ $key ] = isset( $field['choices'][ $value ] ) ? $field['choices'][ $value ] : $value;
		}

		$updated = $push->update_element_selected_options( function( $label ) use ( $meta_value ) {
			return in_array( $label, $meta_value, true );
		} );

		return $updated;
	}

	/**
	 * Get the ACF objects for a post-type and post-id.
	 *
	 * @since  3.1.5
	 *
	 * @param  string  $post_type The post-type to filter by.
	 * @param  integer $post_id   The (optional) post-id to filter by.
	 *
	 * @return bool|array         Array of ACF objects if found, or false.
	 */
	public function get_acfs( $post_type, $post_id = 0 ) {
		$filter = array(
			'post_type'	=> $post_type
		);

		if ( ! empty( $post_id ) ) {
			$filter['post_id'] = $post_id;
		}

		$metabox_ids = (array) apply_filters( 'acf/location/match_field_groups', array(), $filter );

		if ( empty( $metabox_ids ) ) {
			return false;
		}


		$field_groups = (array) apply_filters( 'acf/get_field_groups', array() );
		if ( empty( $field_groups ) ) {
			return false;
		}

		$acfs = array_filter( $field_groups, function( $acf ) use ( $metabox_ids ) {
			return isset( $acf['id'] ) && in_array( $acf['id'], $metabox_ids );
		} );

		return ! empty( $acfs ) ? $acfs : false;
	}

	/**
	 * Get the ACF fields for a given ACF object array.
	 *
	 * @since  3.1.5
	 *
	 * @param  array  $acf The ACF config array.
	 *
	 * @return bool|array  Array of fields for this config, or false.
	 */
	public function get_acf_fields( $acf ) {
		if ( ! isset( $acf['id'] ) ) {
			return false;
		}

		$fields = apply_filters( 'acf/field_group/get_fields', array(), $acf['id'] );
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return false;
		}

		return $fields;
	}

	public function has_pull_transform_method( $type ) {
		return is_callable( array( $this, "maybe_transform_pull_value_{$type}" ) )
			? array( $this, "maybe_transform_pull_value_{$type}" )
			: false;

	}

	public function has_push_transform_method( $type ) {
		return is_callable( array( $this, "maybe_transform_push_value_{$type}" ) )
			? array( $this, "maybe_transform_push_value_{$type}" )
			: false;

	}

}
