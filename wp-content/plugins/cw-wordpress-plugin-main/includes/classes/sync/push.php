<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Sync;

use GatherContent\Importer\Post_Types\Template_Mappings;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;
use WP_Error;

/**
 * Handles pushing content to GC.
 *
 * @since 3.0.0
 */
class Push extends Base {

	/**
	 * Sync direction.
	 *
	 * @var string
	 */
	protected $direction = 'push';

	/**
	 * Post object to push.
	 *
	 * @var int
	 */
	protected $post = null;

	/**
	 * Array of field types completed.
	 *
	 * @var array
	 */
	protected $done = array();

	/**
	 * A json-encoded reference to the original Item config object,
	 * before transformation for the update.
	 *
	 * @var string
	 */
	protected $config = array();
	protected $item_config = array();

	private $item_id = null;

	/**
	 * Creates an instance of this class.
	 *
	 * @param API $api API object.
	 *
	 * @since 3.0.0
	 *
	 */
	public function __construct( API $api ) {
		parent::__construct( $api, new Async_Push_Action() );
	}

	/**
	 * Initiate admin hooks
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function init_hooks() {
		parent::init_hooks();
		add_action( 'wp_async_cwby_push_items', array( $this, 'sync_items' ) );
		add_action( 'wp_async_nopriv_cwby_push_items', array( $this, 'sync_items' ) );
	}

	/**
	 * A method for trying to push directly (without async hooks).
	 *
	 * @param int $mapping_post_id Mapping post ID.
	 *
	 * @return mixed Result of push. WP_Error on failure.
	 * @since  3.0.0
	 *
	 */
	public function maybe_push_item( $mapping_post_id ) {
		try {

			$post       = $this->get_post( $mapping_post_id );
			$mapping_id = \GatherContent\Importer\get_post_mapping_id( $post->ID );

			$this->mapping = Mapping_Post::get( $mapping_id, true );

			$result = $this->do_item( $post->ID );

		} catch ( \Exception $e ) {
			$result = new WP_Error( 'gc_push_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		return $result;
	}

	/**
	 * Pushes WP post to GC after some sanitiy checks.
	 *
	 * @param int $id WP post ID.
	 *
	 * @return mixed Result of push.
	 * @throws Exception On failure.
	 *
	 * @since  3.0.0
	 *
	 */
	protected function do_item( $id ) {

		$this->post = $this->get_post( $id );

		$this->check_mapping_data( $this->mapping );

		$this->set_item( \GatherContent\Importer\get_post_item_id( $this->post->ID ), true );

		$config_update = $this->map_wp_data_to_gc_data();

		// No updated data, so bail.
		if ( empty( $config_update ) ) {

			throw new Exception(
				sprintf( esc_html__( 'No update data found for that post ID: %d', 'content-workflow-by-bynder' ), esc_html($this->post->ID) ),
				__LINE__,
				array(
					'post_id'    => esc_html($this->post->ID),
					'mapping_id' => esc_html($this->mapping->ID),
					'item_id'    => esc_html($this->item->id) ?? 0,
				)
			);
		}

		// If we found updates, do the update.
		return $this->maybe_do_item_update( $config_update );
	}

	/**
	 * Pushes WP post to GC.
	 *
	 * @param array $update The item config update delta array.
	 *
	 * @return mixed Result of push.
	 * @throws Exception On failure.
	 *
	 * @since  3.0.0
	 *
	 */
	public function maybe_do_item_update( $update ) {
		// Get our initial croonfig reference.
		$config = json_decode( $this->config );

		// And update the content with the new values.
		foreach ( $update as $updated_element ) {
			$element_id = $updated_element->name;

			// handle repeatable elements because we stored them in JSON format earlier and GC requires it in array format
			if ( $updated_element->repeatable ) {

				// $repeatable_value = ! empty( $updated_element->value ) ? @json_decode( $updated_element->value, true ) : $updated_element->value;
				if ( is_string( $updated_element->value ) ) {
					$repeatable_value = ! empty( $updated_element->value ) ? json_decode( $updated_element->value, true ) : $updated_element->value;
				} else {
					// Handle the case where $updated_element->value is already an array
					$repeatable_value = $updated_element->value;
				}
				if ( is_array( $repeatable_value ) ) {
					$updated_element->value = $repeatable_value;
				} else {
					$updated_element->value = array();
				}
			}

			// handle new item because we don't have content object for it
			if ( ! isset( $config->content ) ) {
				$config->content = (object) array();
			}

			// finally push it to the content array if the data was changed
			if ( $component_uuid = $updated_element->component_uuid ) {

				if ( ! isset( $config->content->$component_uuid ) ) {
					$config->content->$component_uuid = (object) array();
				}

				if ( is_array( $config->content->$component_uuid ) ) {
					if ( is_array( $updated_element->value ) ) {
						// Directly use the array
						$decoded_value = $updated_element->value;
					} else {
						// Decode JSON string
						$decoded_value = json_decode( $updated_element->value );
						if ( $decoded_value === null && json_last_error() !== JSON_ERROR_NONE ) {
							// JSON decoding failed, handle the error
							// For example, you can log the error or take appropriate action
							// Here, we are setting an empty array
							$decoded_value = [];
						}
					}

					// Handle repeatable components
					$i = 0;
					foreach ( $decoded_value as $value ) {
						if ( isset( $config->content->$component_uuid[ $i ] ) ) {
							$config->content->$component_uuid[ $i ]->$element_id = $value;
						}
						$i ++;
					}
				} else {
					$config->content->$component_uuid->$element_id = $updated_element->value;
				}


			} else {
				$config->content->$element_id = $updated_element->value;
			}
		}

		if ( $this->item_id ) {
			$result = $this->api->uncached()->update_item( $this->item_id, $config );
		} else {
			$result = $this->api->create_item(
				$this->mapping->get_project(),
				$this->mapping->get_template(),
				$this->post->post_title,
				$config->content
			);
		}

		// todo: figure out the structure_uuid scenario which I removed from the old code, because there's no way that scenario can regenerated (@ shehrozsheikh@zao [2021-25-11])

		if ( $result && ! is_wp_error( $result ) ) {
			if ( ! $this->item_id ) {
				\GatherContent\Importer\update_post_item_id( $this->post->ID, $result );
				$this->item_id = $result;
			}

			// If item update was successful, re-fetch it from the API...
			$this->item = $this->api->uncached()->get_item( $this->item_id, true );

			// and update the meta.
			\GatherContent\Importer\update_post_item_meta(
				$this->post->ID,
				array(
					'created_at' => $this->item->created_at,
					'updated_at' => $this->item->updated_at,
				)
			);
		}

		if ( $result === false ) {
			wp_send_json_error( 'Failed to push content to Content Workflow' );
		}

		return $result;
	}

	/**
	 * Sets the item to be pushed to. If it doesn't exist yet, we create it now.
	 *
	 * @param integer $item_id Item id.
	 * @param bool $exclude_status set this to true to avoid appending status data
	 *
	 * @return $item
	 * @throws Exception On failure.
	 *
	 * @since 3.0.0
	 *
	 */
	protected function set_item( $item_id, $exclude_status = false ) {
		$this->item_id = $item_id;

		if ( ! $item_id ) {
			$item = $this->api->get_template( $this->mapping->get_template() );
		} else {
			$item = parent::set_item( $item_id, $exclude_status );
		}

		$this->item_config = $item;
		$this->item        = $item;

		// storing it to compare the changed data later
		$this->config = wp_json_encode( $item );

	}

	/**
	 * Maps the WP post data to the GC item config.
	 *
	 * @return array Item config array on success.
	 * @since  3.0.0
	 *
	 */
	protected function map_wp_data_to_gc_data() {
		$config = $this->loop_item_elements_and_map();

		return apply_filters( 'gc_update_gc_config_data', $config, $this );
	}

	/**
	 * Loops the GC item config elements and maps the WP post data.
	 *
	 * @return array Modified item config array on success.
	 * @since  3.0.0
	 *
	 */
	public function loop_item_elements_and_map() {
		if ( empty( $this->item_config ) ) {
			return false;
		}

		$structure_groups = isset( $this->item_config->related ) ? $this->item_config->related->structure->groups : $this->item_config->structure->groups;

		$this->item_config = array();

		if ( ! isset( $structure_groups ) || empty( $structure_groups ) ) {
			return false;
		}

		// to handle multiple tabs
		foreach ( $structure_groups as $index => $tab ) {
			if ( ! isset( $tab->fields ) || ! $tab->fields ) {
				continue;
			}

			// to handle fields in a tab
			foreach ( $tab->fields as $element_index => $field ) {

				// to handle components with multiple fields inside
				$fields_data    = $field->component->fields ?? array( $field );
				$component_uuid = 'component' === $field->field_type ? $field->uuid : '';

				$is_component_repeatable = false;
				if ( $component_uuid ) {
					$metadata                = $field->metadata;
					$is_component_repeatable = ( is_object( $metadata ) && isset( $metadata->repeatable ) ) ? $metadata->repeatable->isRepeatable : false;
				}

				foreach ( $fields_data as $field_data ) {

					$this->element = (object) $this->format_element_data( $field_data, $component_uuid, false, $is_component_repeatable );

					if ( $component_uuid ) {
						$this->element->component_uuid = $component_uuid;
					}

					$uuid = $this->element->name;
					if ( $component_uuid ) {
						$this->element->component_uuid = $component_uuid;
						$uuid                          = $component_uuid . '_component_' . $component_uuid;
					}

					$source      = $this->mapping->data( $uuid );
					$source_type = isset( $source['type'] ) ? $source['type'] : '';

					// Check if $source['field'] exists, then use it as the key
					if ( isset( $source['field'] ) ) {
						// not sure if the field can be empty, will need to check that later on
						$source_key = $source['field'];
					} else {
						// If $source['field'] doesn't exist, fall back to using $source['value']
						$source_key = isset( $source['value'] ) ? $source['value'] : '';
					}


					if ( $source_type ) {
						if ( ! isset( $this->done[ $source_type ] ) ) {
							$this->done[ $source_type ] = array();
						}

						if ( ! isset( $this->done[ $source_type ][ $source_key ] ) ) {
							$this->done[ $source_type ][ $source_key ] = array();
						}

						$this->done[ $source_type ][ $source_key ][ $index . ':' . $element_index ] = (array) $this->element;
					}

					if (
						$source
						&& isset( $source['type'], $source['value'] )
						&& $this->set_values_from_wp( $source_type, $source_key )
					) {
						$this->item_config[] = $this->element;
					}
				}
			}
		}


		$this->remove_unknowns();

		return $this->item_config;
	}

	/**
	 * Loops the $done array and looks for duplicates (unknowns) and removes them.
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 * @todo Fix this. Probably need a reverse mapping UI for each item push, or something.
	 *
	 */
	protected function remove_unknowns() {
		foreach ( $this->done as $source_type => $keys ) {
			foreach ( $keys as $source_key => $values ) {
				if ( count( $values ) < 2 ) {
					// We're good to go!
					continue;
				}

				/*
				 * @todo fix this.
				 * UH OH, this means we've encountered some appendable field types which
				 * have more than one GC value mapping to them. We don't have a reliable
				 * way of parsing those bits back to the individual GC fields, So we have
				 * to simply remove them from the update.
				 */

				foreach ( $values as $key => $value ) {
					$keys = explode( ':', $key );

					if ( isset( $this->item_config[ $keys[0] ]->elements[ $keys[1] ] ) ) {
						unset( $this->item_config[ $keys[0] ]->elements[ $keys[1] ] );
					}

					if ( empty( $this->item_config[ $keys[0] ]->elements ) ) {
						unset( $this->item_config[ $keys[0] ] );
					}
				}
			}
		}
	}

	/**
	 * Sets the item config element value, if it is determeined that the value changed.
	 *
	 * @param string $source_type The data source type.
	 * @param string $source_key The data source key.
	 *
	 * @return array $updated Whether value was updated.
	 * @since 3.0.0
	 *
	 */
	protected function set_values_from_wp( $source_type, $source_key ) {
		$updated = false;

		switch ( $source_type ) {
			case 'wp-type-post':
				$updated = $this->set_post_field_value( $source_key );
				break;

			case 'wp-type-taxonomy':
				$updated = $this->set_taxonomy_field_value( $source_key );
				break;

			case 'wp-type-meta':
				$updated = $this->set_meta_field_value( $source_key );
				break;

			case 'wp-type-media':
				$this->set_featured_image_alt( $source_key );
				break;

			case 'wp-type-acf':
				$updated = $this->set_acf_field_value( $source_key );
				break;

			case 'wp-type-database':
				$updated = $this->set_database_field_value( $source_key );
				break;
		}

		return $updated;
	}


	/**
	 * Updates the featured image alt_text if changed
	 *
	 * @param string $source_key source key.
	 *
	 * @return void
	 * @since 3.2.0
	 *
	 */
	protected function set_featured_image_alt( $source_key ) {

		if ( 'featured_image' !== $source_key ) {
			return;
		}

		$attach_id = get_post_thumbnail_id( $this->post->ID );

		if ( ! $attach_id ) {
			return;
		}

		if ( $meta = \GatherContent\Importer\get_post_item_meta( $attach_id ) ) {

			$old_alt_text     = $meta['alt_text'] ?? '';
			$updated_alt_text = get_post_meta( $attach_id, '_wp_attachment_image_alt', true );

			if ( $old_alt_text !== $updated_alt_text && isset( $meta['file_id'] ) ) {

				$meta['alt_text'] = $updated_alt_text ?? '';

				if ( empty( $meta['alt_text'] ) ) {
					return;
				}

				$result = $this->api->update_file_meta(
					$this->mapping->get_project(),
					$meta['file_id'],
					array(
						'alt_text' => $meta['alt_text'],
					)
				);

				if ( ! $result ) {
					return;
				}

				// update the new alt_text in the attachment meta
				\GatherContent\Importer\update_post_item_meta(
					$attach_id,
					$meta
				);

			}
		}
	}


	/**
	 * Sets the item config element value for WP post fields,
	 * if it is determeined that the value changed.
	 *
	 * @param string $post_column The post data column.
	 *
	 * @return bool $updated Whether value was updated.
	 * @since 3.0.0
	 *
	 */
	protected function set_post_field_value( $post_column ) {
		$updated  = false;
		$el_value = $this->element->value;

		$value = ! empty( $this->post->{$post_column} ) ? self::remove_zero_width( $this->post->{$post_column} ) : false;
		$value = apply_filters( "gc_get_{$post_column}", $value, $this );

		// Make element value match the WP versions formatting, to see if they are equal.
		switch ( $post_column ) {
			case 'post_title':
				$el_value = wp_kses_post( $this->get_element_value() );
				break;
			case 'post_content':
			case 'post_excerpt':
				$el_value = wp_kses_post( $this->get_element_value() );
				if ( 'post_content' === $post_column ) {
					$value = $this->ensureShortcodesAreNotConvertedToHtml(
						function ( $value ) {
							return apply_filters( 'the_content', $value );
						},
						$value
					);
				}

				// There are super minor encoding issues we want to ignore.
				similar_text( $value, $el_value, $percent_similarity );
				if ( $percent_similarity > 99.9 ) {
					$value = $el_value;
				}
				break;
		}
		// @codingStandardsIgnoreStart
		// We don't necessarily want strict comparison here.
		if ( $value != $el_value ) {
			// @codingStandardsIgnoreEnd
			$this->element->value = $value;
			$updated              = true;
		}

		return $updated;
	}

	protected function set_database_field_value( $tableColumnString ) {
		$updated  = false;
		$el_value = $this->element->value;

		$parts = explode( '.', $tableColumnString );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$table  = $parts[0];
		$column = $parts[1];

		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT %s as value from %s where post_id=%d;", $column, $table, $this->post->ID ) );

		$value = $results[0]->value;
		// @codingStandardsIgnoreStart
		// We don't necessarily want strict comparison here.
		if ( $value != $el_value ) {
			// @codingStandardsIgnoreEnd
			$this->element->value = $value;
			$updated              = true;
		}

		return $updated;
	}

	private function ensureShortcodesAreNotConvertedToHtml( callable $callback, string $value ): string {
		preg_match_all(
			'/\[[\w\W]+?\]/',
			$value,
			$matches
		);

		foreach ( $matches[0] as $match ) {
			$value = str_replace(
				$match,
				'<shortcode>' . base64_encode( $match ) . '</shortcode>',
				$value
			);
		}

		$value = $callback( $value );

		preg_match_all(
			'/<shortcode>([\w\W])+?<\/shortcode>/',
			$value,
			$matches
		);

		foreach ( $matches[0] as $match ) {
			$value = str_replace(
				$match,
				base64_decode( wp_strip_all_tags( $match ) ),
				$value
			);
		}

		return $value;
	}

	/**
	 * Sets the item config element value for WP taxonomy terms,
	 * if it is determeined that the value changed.
	 *
	 * @param string $taxonomy The taxonomy name.
	 *
	 * @return bool $updated Whether value was updated.
	 * @since 3.0.0
	 *
	 */
	protected function set_taxonomy_field_value( $taxonomy ) {
		$terms      = get_the_terms( $this->post, $taxonomy );
		$term_names = ! is_wp_error( $terms ) && ! empty( $terms )
			? wp_list_pluck( $terms, 'name' )
			: array();

		$updated = $this->set_taxonomy_field_value_from_names( $term_names );

		return apply_filters( 'cwby_config_taxonomy_field_value_updated', $updated, $taxonomy, $terms, $this );
	}

	public function set_taxonomy_field_value_from_names( $term_names ) {
		$updated = false;

		switch ( $this->element->type ) {

			case 'text':
				$item_vals = array_map( 'trim', explode( ',', $this->element->value ) );

				$diff = array_diff( $term_names, $item_vals );
				if ( empty( $diff ) ) {
					$diff = array_diff( $item_vals, $term_names );
				}

				if ( ! empty( $diff ) ) {
					$this->element->value = ! empty( $term_names ) ? implode( ', ', $term_names ) : '';
					$updated              = true;
				}
				break;

			case 'choice_checkbox':
			case 'choice_radio':
				$updated = $this->update_element_selected_options(
					function ( $label ) use ( $term_names ) {
						return in_array( $label, $term_names, true );
					}
				);

				// @codingStandardsIgnoreStart
				/*
				 * Probably can't create options via the API.
				 *
				 * @todo we'll leave this for the future, in case you can.
				 *
				 * $option_names = wp_list_pluck( $this->element->options, 'label' );
				 * $new_terms = array_diff( $term_names, $option_names );
				 * foreach ( $new_terms as $new_term ) {
				 * 	$this->element->options[] = (object) array(
				 * 		'label' => $new_term,
				 * 		'selected' => true,
				 * 	)
				 * }
				 */
				// @codingStandardsIgnoreEnd
				break;

		}

		return $updated;
	}

	/**
	 * Sets the item config element value for WP meta fields,
	 * if it is determeined that the value changed.
	 *
	 * @param string $meta_key The meta key.
	 *
	 * @return bool $updated Whether value was updated.
	 * @since 3.0.0
	 *
	 */
	protected function set_meta_field_value( $meta_key ) {
		$updated    = false;
		$meta_value = get_post_meta( $this->post->ID, $meta_key, 1 );

		$check = apply_filters( 'cwby_config_pre_meta_field_value_updated', null, $meta_value, $meta_key, $this );
		if ( null !== $check ) {
			return $check;
		}

		switch ( $this->element->type ) {

			case 'text':
				// @codingStandardsIgnoreStart
				// We don't necessarily want strict comparison here.
				if ( $meta_value != $this->element->value ) {
					// @codingStandardsIgnoreEnd
					$this->element->value = $meta_value;
					$updated              = true;
				}
				break;

			case 'choice_radio':
				$updated = $this->update_element_selected_options(
					function ( $label ) use ( $meta_value ) {
						return $meta_value === $label;
					}
				);
				break;

			case 'choice_checkbox':
				if ( empty( $meta_value ) ) {
					$meta_value = array();
				} else {
					$meta_value = is_array( $meta_value ) ? $meta_value : array( $meta_value );
				}

				$updated = $this->update_element_selected_options(
					function ( $label ) use ( $meta_value ) {
						return in_array( $label, $meta_value, true );
					}
				);
				break;

		}

		return apply_filters( 'gc_config_meta_field_value_updated', $updated, $meta_value, $meta_key, $this );
	}


	/**
	 * Sets the item config element value for ACF fields,
	 * if it is determeined that the value changed.
	 *
	 * @param string $group_key The ACF group key.
	 * @param string $field_key The ACF field key.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return bool $updated Whether value was updated.
	 * @since 3.0.0
	 *
	 */

	protected function set_acf_field_value( $group_key ) {
		$updated = false;

		// Get the post ID
		$post_id = $this->post->ID;

		// Fetch the ACF field group using the group key
		$field_group = get_field( $group_key, $post_id );

		$el       = $this->element;
		$el_value = $this->element->value;
		if ( is_object( $el ) && property_exists( $el, 'component_uuid' ) ) {
			// We have a component here
			$structure_groups    = $this->item->structure->groups;
			$componentFieldsKeys = [];

			foreach ( $structure_groups as $group ) {
				$fields = $group->fields;
				foreach ( $fields as $field ) {
					if ( $field->uuid == $el->component_uuid ) {
						$component = $field->component;
						// Check if the component property exists and is an object
						if ( is_object( $component ) && property_exists( $component, 'fields' ) ) {
							// Access the fields property of the component object
							$componentFields = $component->fields;
							foreach ( $componentFields as $componentField ) {
								$componentFieldsKeys[] = $componentField->uuid;
							}
						}
					}
				}
			}
			$groupData = [];
			foreach ( $field_group as $group ) {
				// Combine keys from componentFieldsKeys with values from the current group
				$new_group   = array_combine( $componentFieldsKeys, $group );
				$groupData[] = $new_group;
			}


			// Define a mapping between field types and processing functions
			$fieldTypeProcessors = [
				'text'            => 'processTextField',
				'attachment'      => 'processAttachmentField',
				'choice_checkbox' => 'processChoiceCheckboxField',
				'choice_radio'    => 'processChoiceRadioField',
				// Add more field types and corresponding processing functions as needed
			];

			// Initialize an associative array to store grouped items
			$groupedData = [];
			// Iterate through each group data
			foreach ( $groupData as $dataInstance ) {
				// Iterate through each field UUID and its corresponding value
				foreach ( $dataInstance as $field_uuid => $field_value ) {
					// Check if the field UUID exists as a key in the grouped data array
					if ( ! isset( $groupedData[ $field_uuid ] ) ) {
						// If the key doesn't exist, initialize it as an empty array
						$groupedData[ $field_uuid ] = [];
					}
					// Append the field value to the corresponding key in the grouped data array
					$groupedData[ $field_uuid ][] = $field_value;
				}
			}

			// Iterate through each field type and its corresponding field UUIDs
			foreach ( $groupedData as $field_uuid => $field_values ) {
				// Process field values based on field type
				$field_type = null;
				foreach ( $componentFields as $componentField ) {
					if ( $componentField->uuid === $field_uuid ) {
						$field_type = $componentField->field_type;
						break; // Stop iterating once the field with the matching UUID is found
					}
				}

				if ( isset( $fieldTypeProcessors[ $field_type ] ) ) {
					if ( $this->element->name == $field_uuid ) {
						// Call the corresponding processing function for each field UUID
						$processorFunction = $fieldTypeProcessors[ $field_type ];
						$processorResult   = $this->$processorFunction( $field_values );

						// Check if the result is an array with 'options' and 'value'
						if ( is_array( $processorResult ) && array_key_exists( 'options', $processorResult ) && array_key_exists( 'value', $processorResult ) ) {
							// If the result contains both 'options' and 'value', extract them
							$jsonOptions = $processorResult['options'];
							$jsonValue   = $processorResult['value'];
						} else {
							// If the result is not an array with 'options' and 'value', assume it's just the value
							$jsonValue = $processorResult;
						}
					}


					// Assign the processed value to the corresponding element
					if ( ( $this->element->name == $field_uuid ) && ( $this->element->value != $jsonValue ) ) {
						$this->element->value = $jsonValue;
						if ( $jsonOptions ) {
							$this->element->options = $jsonOptions;
						}
						$updated = true;
					}
				} else {
					// Handle unknown field types or skip
				}
			}
		} else {
			$outputArray = array();
			foreach ( $field_group as $item ) {
				$values = array_values( $item );
				$outputArray[] = wp_json_encode( $values[0] );
			}

			$jsonValue = '[' . implode( ',', $outputArray ) . ']';
			if ( $this->element->value != $jsonValue ) {
				$this->element->value = $jsonValue;
				$updated              = true;
			}
		}

		return $updated;
	}

	protected function processTextField( $field_value ) {
		// Handle text field type
		$jsonValues = []; // Array to store JSON encoded values

		// Check if the field value is an array
		if ( is_array( $field_value ) ) {
			foreach ( $field_value as $item ) {
				// Check if the item is an array
				if ( is_array( $item ) ) {
					// If the item is an array, encode its elements separately
					$encodedValues = [];
					foreach ( $item as $value ) {
						if ( ! is_array( $value ) ) {
							// If the value is not an array, encode it directly
							$trimmedValue    = rtrim( $value, "\n\r" );
							$encodedValues[] = '"' . addslashes( $trimmedValue ) . '"';
						} else {
							// If the value is an array, encode its elements separately
							$encodedInnerValues = [];
							foreach ( $value as $innerValue ) {
								$trimmedValue         = rtrim( $innerValue, "\n\r" );
								$encodedInnerValues[] = '"' . addslashes( $trimmedValue ) . '"';
							}
							// Encode the inner array as a JSON array
							$encodedValues[] = implode( ',', $encodedInnerValues );
						}
					}
					// Encode the outer array as a JSON array
					$jsonValues[] = '[' . implode( ',', $encodedValues ) . ']';
				} else {
					// If the item is not an array, encode it directly
					$trimmedValue = rtrim( $item, "\n\r" );
					$jsonValues[] = '"' . addslashes( $trimmedValue ) . '"';
				}
			}

		} else {
			// If the field value is not an array, encode it directly
			$trimmedValue = rtrim( $field_value, "\n\r" );
			$jsonValues[] = '"' . addslashes( $trimmedValue ) . '"';
		}

		// Return the JSON encoded values
		return '[' . implode( ',', $jsonValues ) . ']';
	}

	protected function processAttachmentField( $field_value ) {
		// Check if the field value is empty or not set
		if ( empty( $field_value ) ) {
			// Field value is empty, meaning attachments should be removed
			return '[]'; // Send an empty array to remove all attachments
		} else {
			// Field value is not empty, meaning attachments are provided

			// Initialize an array to store file IDs for attachments
			$fileIds = [];

			// Loop through each attachment data in the field value array
			foreach ( $field_value as $attachment ) {
				// Check if the attachment has an 'ID' key
				if ( isset( $attachment['ID'] ) ) {
					// Attachment already exists, add its ID to the file IDs array
					$fileIds[] = $attachment['ID'];
				}
				// If you want to upload new files, you can handle it here
				// Upload the new files via multipart/form-data as described in the GatherContent API documentation
			}

			// Return the file IDs as a JSON array
			return wp_json_encode( $fileIds );
		}
	}

	protected function processChoiceCheckboxField( $field_value ) {
		$options        = $this->element->options; // Get the options
		$result_options = []; // Array to store the result for options
		$result_value   = []; // Array to store the result for value

		// Iterate through each item in the field value array
		foreach ( $field_value as $value ) {
			$selected_options = []; // Array to store options for this value
			$selected_value   = []; // Array to store value for this value

			// Iterate through each option and set 'selected' property accordingly
			foreach ( $options as $option ) {
				$selected_option = clone $option; // Clone the option object

				// Set 'selected' property based on whether the label matches any value in the current $field_value item
				$selected_option->selected = in_array( $option->label, $value ) ? 1 : 0;

				// Add the modified option to the array
				$selected_options[] = $selected_option;

				// If the label matches any value in the current $field_value item, store the value details
				if ( in_array( $option->label, $value ) ) {
					$selected_value[] = [ 'id' => $option->name, 'label' => $option->label ];
				}
			}

			// Add the array of options for this value to the result
			$result_options[] = $selected_options;

			// Add the value details to the result
			$result_value[] = $selected_value;
		}

		return [ 'options' => $result_options, 'value' => $result_value ]; // Return both results
	}

	protected function processChoiceRadioField( $field_value ) {
		$options        = $this->element->options; // Get the options
		$result_options = []; // Array to store the result for options
		$result_value   = []; // Array to store the result for value

		// Iterate through each item in the field value array
		foreach ( $field_value as $value ) {
			$selected_options = []; // Array to store options for this value
			$selected_value   = []; // Array to store value for this value

			// Iterate through each option and set 'selected' property accordingly
			foreach ( $options as $option ) {
				$selected_option = clone $option; // Clone the option object

				// Set 'selected' property based on whether the label matches the value
				$selected_option->selected = ( $option->label === $value ) ? 1 : 0;

				// Add the modified option to the array
				$selected_options[] = $selected_option;

				// If the label matches the value, store the value details
				if ( $option->label === $value ) {
					$selected_value[] = [ 'id' => $option->name, 'label' => $option->label ];
				}
			}

			// Add the array of options for this value to the result
			$result_options[] = $selected_options;

			// Add the value details to the result
			$result_value[] = $selected_value;
		}

		return [ 'options' => $result_options, 'value' => $result_value ]; // Return both results
	}


	/**
	 * Uses $callback to determine if each option value should be selected,
	 *
	 * @param callable $callback Closure.
	 *
	 * @return bool             Whether the options were updated or not.
	 * @since  3.0.0
	 *
	 */
	public function update_element_selected_options( $callback ) {
		$pre_options = wp_json_encode( $this->element->value );

		$last_key = false;
		if ( isset( $this->element->other_option ) && $this->element->other_option ) {
			$keys     = array_keys( $this->element->options );
			$last_key = end( $keys );
		}

		$values = [];
		foreach ( $this->element->options as $key => $option ) {

			// If it's the "Other" option, we need to use the option's value, not label.
			$label = $last_key === $key && isset( $option->value )
				? $option->value
				: $option->label;

			if ( $callback( self::remove_zero_width( $label ) ) ) {
				$values[] = [
					'id'    => $option->name,
					'label' => $option->label,
				];
			} else {
				$this->element->options[ $key ]->selected = false;

				// Else GC API error:
				// "Other option value must be empty when other option not selected".
				if ( $last_key === $key ) {
					$this->element->options[ $key ]->value = '';
				}
			}
		}

		$this->element->value = $values;
		$post_options         = wp_json_encode( $this->element->value );

		// @codingStandardsIgnoreStart
		// Check if the values have been updated.
		// We don't necessarily want strict comparison here.
		return $pre_options != $post_options;
		// @codingStandardsIgnoreEnd
	}

}
