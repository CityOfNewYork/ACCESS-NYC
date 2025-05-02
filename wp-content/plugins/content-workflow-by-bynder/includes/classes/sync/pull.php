<?php

/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Sync;

use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;
use Mimey\MimeTypes;
use WP_Error;

/**
 * Handles pulling content from GC.
 *
 * @since 3.0.0
 */
class Pull extends Base {

	/**
	 * Sync direction.
	 *
	 * @var string
	 */
	protected $direction = 'pull';

	/**
	 * Creates an instance of this class.
	 *
	 * @param API $api API object.
	 *
	 * @since 3.0.0
	 *
	 */
	public function __construct( API $api ) {
		parent::__construct( $api, new Async_Pull_Action() );
	}

	/**
	 * Initiate plugins_loaded hooks.
	 *
	 * @return void
	 * @since  3.0.2
	 *
	 */
	public static function init_plugins_loaded_hooks() {
		add_action( 'cwby_associate_hierarchy', array( __CLASS__, 'associate_hierarchy' ) );
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
		add_action( 'wp_async_cwby_pull_items', array( $this, 'sync_items' ) );
		add_action( 'wp_async_nopriv_cwby_pull_items', array( $this, 'sync_items' ) );
		add_action( 'cwby_pull_complete', array( __CLASS__, 'associate_hierarchy' ) );
	}

	/**
	 * A method for trying to pull directly (without async hooks).
	 *
	 * @param int $mapping_post Mapping post ID or object.
	 * @param int $item_id GC item id.
	 *
	 * @return mixed Result of pull. WP_Error on failure.
	 * @since  3.0.0
	 *
	 */
	public function maybe_pull_item( $mapping_post, $item_id ) {

		try {
			$this->mapping = Mapping_Post::get( $mapping_post, true );
			$result        = $this->do_item( $item_id );
		} catch ( \Exception $e ) {
			$result = new WP_Error( 'cwby_pull_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		return $result;
	}

	/**
	 * @param string $table 'wp_aioseo_posts'
	 * @param string $column 'title'
	 * @param string $postIdColumn 'post_id'
	 * @param int $post_id 123
	 * @param string $content 'Some great and cool content'
	 *
	 * @return bool true on success
	 */
	public function saveContentToTable(
		string $table,
		string $column,
		string $postIdColumn,
		int $post_id,
		string $content
	) {
		global $wpdb;

		$data  = [ $column => $content ];
		$where = [ $postIdColumn => $post_id ];
		$wpdb->update( $table, $data, $where ); // false | int

		return true;
	}

	/**
	 * @TODO restrict what tables / columns can be used.
	 *
	 * @param string $tableColumnString "tableName.columnName"
	 *
	 * @return false|string[]
	 */
	private function isTableColumnStringValid( string $tableColumnString ) {
		global $wpdb;

		$parts = explode( '.', $tableColumnString );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$table  = $parts[0];
		$column = $parts[1];

		$results = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM %1s;", $table ) );

		foreach ( $results as $row ) {
			if ( $row->Field === $column ) {
				return [ $table, $column ];
			}
		}

		return false;
	}

	private function handleDatabaseMappings( array $databaseMappings, int $post_id ) {
		foreach ( $databaseMappings as $tableAndColumn => $content ) {

			$parts = $this->isTableColumnStringValid( $tableAndColumn );
			if ( ! $parts ) {
				continue;
			}

			$table  = $parts[0];
			$column = $parts[1];

			$success = $this->saveContentToTable(
				$table, $column, 'post_id', $post_id, $content
			);

			if ( ! $success ) {
				throw new Exception( 'Failed to save content to table', 500, [
					'table'   => wp_kses_post( $table ),
					'column'  => wp_kses_post( $column ),
					'post_id' => esc_html( $post_id ),
					'content' => wp_kses_post( $content ),
				] );
			}
		}
	}

	/**
	 * Pulls GC item to update a post after some sanitiy checks.
	 *
	 * @param int $id GC Item ID.
	 *
	 * @return mixed Result of pull.
	 * @throws Exception On failure.
	 *
	 * @since  3.0.0
	 *
	 */
	protected function do_item( $id ) {
		$roundTwo = false;

		$this->check_mapping_data( $this->mapping );

		$this->set_item( $id );

		$post_data   = array();
		$attachments = $tax_terms = false;

		if ( $existing = \GatherContent\Importer\get_post_by_item_id( $id ) ) {

			// Check if item is up-to-date and if pull is necessary.
			$meta       = \GatherContent\Importer\get_post_item_meta( $existing->ID );
			$updated_at = isset( $meta['updated_at'] ) ? $meta['updated_at'] : 0;

			if (
				// Check if we have updated_at values to compare.
				isset( $this->item->updated_at ) && ! empty( $updated_at )
				// And if we do, compare them to see if GC item is newer.
				&& ( $is_up_to_date = strtotime( $this->item->updated_at ) <= strtotime( $updated_at ) )
				// If it's not newer, then don't update (unless asked to via filter).
				&& $is_up_to_date && apply_filters( 'cwby_only_update_if_newer', true )
			) {
				throw new Exception(
					sprintf( esc_html__( 'WordPress has most recent changes for %1$s (Item ID: %2$d):', 'content-workflow-by-bynder' ), esc_html( $this->item->name ), esc_html( $this->item->id ) ),
					__LINE__,
					array(
						'post' => esc_html( $existing->ID ),
						'item' => esc_html( $this->item->id ),
					)
				);
			}

			$post_data = (array) $existing;
		} else {
			$post_data['ID'] = 0;
			$roundTwo        = true;
		}

		$post_data = $this->map_cwby_data_to_wp_data( $post_data );

		if ( ! empty( $post_data['attachments'] ) ) {
			$attachments = $post_data['attachments'];
			unset( $post_data['attachments'] );
		}

		if ( ! empty( $post_data['tax_input'] ) ) {
			$tax_terms = $post_data['tax_input'];
			unset( $post_data['tax_input'] );
		}

		/**
		 * Keep any wp-type-database mappings but remove them from post_data as
		 * these are saved into the main wp_posts table
		 */
		$databaseMappings = $post_data['database'] ?? [];
		unset( $post_data['database'] );

		$post_id = wp_insert_post( $post_data, 1 );

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( esc_html( $post_id->get_error_message() ), __LINE__, wp_kses_post( print_r( $post_id->get_error_data(), true ) ) );
		}

		$post_data['ID'] = $post_id;

		// Store item ID reference to post-meta.
		\GatherContent\Importer\update_post_item_id( $post_id, $id );
		\GatherContent\Importer\update_post_mapping_id( $post_id, $this->mapping->ID );
		\GatherContent\Importer\update_post_item_meta(
			$post_id,
			array(
				'created_at' => $this->item->created_at,
				'updated_at' => $this->item->updated_at,
			)
		);

		if ( ! empty( $databaseMappings ) ) {
			$this->handleDatabaseMappings( $databaseMappings, $post_id );
		}

		if ( ! empty( $tax_terms ) ) {
			foreach ( $tax_terms as $taxonomy => $terms ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( ! $taxonomy_obj ) {
					/* translators: %s: taxonomy name */
					_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'Invalid taxonomy: %s.' ), esc_html( $taxonomy ) ), '4.4.0' );
					continue;
				}

				// array = hierarchical, string = non-hierarchical.
				if ( is_array( $terms ) ) {
					$terms = array_filter( $terms );
				}

				// Set post terms without the cap-check.
				wp_set_post_terms( $post_id, $terms, $taxonomy );
			}
		}

		$updated_post_data = array();

		if ( $attachments ) {
			$attachments  = apply_filters( 'cwby_media_objects', $attachments, $post_data );
			$replacements = $this->sideload_attachments( $attachments, $post_data );

			if ( ! empty( $replacements ) ) {
				// Do replacements.
				if ( ! empty( $replacements['post_content'] ) ) {
					$updated_post_data['post_content'] = strtr( $post_data['post_content'], $replacements['post_content'] );
				}

				if ( ! empty( $replacements['post_excerpt'] ) ) {
					$updated_post_data['post_excerpt'] = strtr( $post_data['post_excerpt'], $replacements['post_excerpt'] );
				}

				if ( ! empty( $replacements['meta_input'] ) ) {
					$updated_post_data['meta_input'] = array_map(
						function ( $meta ) {
							return is_array( $meta ) && count( $meta ) > 1
								? wp_json_encode( $meta )
								: array_shift( $meta );
						},
						$replacements['meta_input']
					);
				}
			}
		}

		if ( ! empty( $updated_post_data ) ) {
			// And update post (but don't create a revision for it).
			self::post_update_no_revision( array_merge( $post_data, $updated_post_data ) );
		}

		if ( $status = $this->mapping->get_item_new_status( $this->item ) ) {
			// Update the GC item status.
			$this->api->set_item_status( $id, $status );
		}

		// Call methods the second time to get post working for the acf fields.
		// Componentes are attached to posts, meaning we need the post ID to attach the component.
		// When we first create a post (not exisiting), we set the ID = 0 before we later create the wp_post and then update the post_ID
		// In set_acf_field_value, we require the correct post ID to be able to attach the component (ACF field) to the post which gets updated to the correct one later after the post is created.
		// Hence the need to run it the second time when we have the correct ID.
		// If we do not do it this way, it is going to look for the post with ID = 0 always to attach the component to and that will be wrong.
		// We can later look for a beter approach to handle this but this works just fine.
		if ( $roundTwo == true ) {
			$post_data = $this->set_acf_field_value( $post_data );
		}

		return $post_id;
	}

	/**
	 * Maps the GC item config data to WP data.
	 *
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array Item config array on success.
	 * @since  3.0.0
	 *
	 */
	protected function map_cwby_data_to_wp_data( $post_data = array() ) {

		$this->check_mapping_data( $this->mapping );

		foreach ( array( 'post_author', 'post_status', 'post_type' ) as $key ) {
			$post_data[ $key ] = $this->mapping->data( $key );
		}

		$status = $this->mapping->get_wp_status_for_item( $this->item );
		if ( $status && 'nochange' !== $status ) {
			$post_data['post_status'] = $status;
		}

		$backup = array();
		foreach ( $this->append_types as $key ) {
			$backup[ $key ]    = isset( $post_data[ $key ] ) ? $post_data[ $key ] : '';
			$post_data[ $key ] = 'gcinitial';
		}

		if ( $this->should_map_hierarchy( $post_data['post_type'] ) && isset( $this->item->position ) ) {
			$post_data['menu_order'] = absint( $this->item->position );
		}

		$post_data = $this->loop_item_elements_and_map( $post_data );

		// Put the backup data back.
		foreach ( $backup as $key => $value ) {
			if ( 'gcinitial' === $post_data[ $key ] ) {
				$post_data[ $key ] = $value;
			}
		}

		if ( $this->should_update_title_with_item_name( $post_data ) ) {
			$post_data['post_title'] = sanitize_text_field( $this->item->name );
		}

		if ( ! empty( $post_data['ID'] ) ) {
			$post_data = apply_filters( 'cwby_update_wp_post_data', $post_data, $this );
		} else {
			$post_data = apply_filters( 'cwby_new_wp_post_data', $post_data, $this );
		}

		return $post_data;
	}

	/**
	 * Check if post title should be updated with item title.
	 *
	 * Only if the post title is empty, or there is no post_title field mapped.
	 *
	 * @return boolean
	 * @since  3.1.8
	 *
	 */
	public function should_update_title_with_item_name( $post_data ) {
		$should      = ! empty( $this->item->name );
		$empty_title = empty( $post_data['post_title'] );
		if ( ! $empty_title ) {
			$should = ! $this->has_post_title_mapping();
		}

		return $should;
	}

	/**
	 * Check if mapping has a mapping for the post_title. (To fallback to item title)
	 *
	 * @return boolean
	 * @since  3.1.8
	 *
	 */
	public function has_post_title_mapping() {
		try {
			array_filter(
				$this->mapping->data(),
				function ( $mapped ) {
					if (
						isset( $mapped['type'], $mapped['value'] )
						&& 'wp-type-post' === $mapped['type']
						&& 'post_title' === $mapped['value']
					) {
						throw new \Exception( 'found' );
					}
				}
			);
		} catch ( \Exception $e ) {
			return true;
		}

		return false;
	}


	/**
	 * Loops the GC item content elements and maps the WP post data.
	 *
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array Modified post data array on success.
	 * @since  3.0.0
	 *
	 */
	protected function loop_item_elements_and_map( $post_data ) {

		$structure_groups = $this->item->structure->groups;

		if ( ! isset( $structure_groups ) || empty( $structure_groups ) ) {
			return $post_data;
		}

		$columns = array();

		// to handle multiple tabs
		foreach ( $structure_groups as $tab ) {
			if ( ! isset( $tab->fields ) || ! $tab->fields ) {
				continue;
			}

			// to handle fields in a tab
			foreach ( $tab->fields as $field ) {

				// to handle components with multiple fields inside
				$fields_data    = $field->component->fields ?? array( $field );
				$component_uuid = 'component' === $field->field_type ? $field->uuid : '';

				$is_component_repeatable = false;
				if ( $component_uuid ) {
					$metadata                = $field->metadata;
					$is_component_repeatable = ( is_object( $metadata ) && isset( $metadata->repeatable ) ) ? $metadata->repeatable->isRepeatable : false;
				}

				$componentProcessed = false; // Initialize flag outside the loop

				foreach ( $fields_data as $field_data ) {
					$this->element = (object) $this->format_element_data( $field_data, $component_uuid, true, $is_component_repeatable );
					$uuid          = $this->element->name;

					// Check if "_component_" exists in the string and if it has not been processed yet
					if ( strpos( $uuid, "_component_" ) !== false && ! $componentProcessed ) {
						// Split the string by "_component_"
						$parts = explode( "_component_", $uuid );

						// Get the last part
						$uuid = end( $parts );

						// Concatenate the last part with the prefix "_component_"
						$uuid = $uuid . "_component_" . $uuid;

						// Set the flag to true to indicate that "_component_" UUID has been processed
						$componentProcessed = true;
					}

					// Further processing with the UUID
					$destination = $this->mapping->data( $uuid );
					if ( $destination && isset( $destination['type'], $destination['value'] ) ) {
						$columns[ $destination['value'] ] = true;
						$post_data                        = $this->set_post_values( $destination, $post_data );
					}
				}

			}
		}

		if ( isset( $columns['post_date'] ) && ! isset( $columns['post_date_gmt'] ) ) {
			$post_data['post_date_gmt'] = get_gmt_from_date( $post_data['post_date'] );
		}

		if ( isset( $columns['post_modified'] ) && ! isset( $columns['post_modified_gmt'] ) ) {
			$post_data['post_modified_gmt'] = get_gmt_from_date( $post_data['post_modified'] );
		}

		return $post_data;
	}

	/**
	 * Sets the post data value, for each data type.
	 *
	 * @param array $destination Destination array, includes type and value.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array $post_data   The modified WP Post data array.
	 * @since 3.0.0
	 *
	 */
	protected function set_post_values( $destination, $post_data ) {

		$this->set_element_value();
		try {
			switch ( $destination['type'] ) {

				case 'wp-type-post':
					$post_data = $this->set_post_field_value( $destination['value'], $post_data );
					break;

				case 'wp-type-taxonomy':
					$post_data = $this->set_taxonomy_field_value( $destination['value'], $post_data );
					break;

				case 'wp-type-meta':
					$post_data = $this->set_meta_field_value( $destination['value'], $post_data );
					break;

				case 'wp-type-media':
					$post_data = $this->set_media_field_value( $destination['value'], $post_data );
					break;

				case 'wp-type-acf':
					$post_data = $this->set_acf_field_value( $post_data );
					break;

				case 'wp-type-database':
					$post_data = $this->set_database_field_value( $destination['value'], $post_data );
					break;
			}
			// @codingStandardsIgnoreStart
		} catch ( \Exception $e ) {
			// @todo logging?
		}

		// @codingStandardsIgnoreEnd

		return $post_data;
	}

	/**
	 * Sets the WP post fields based on the item config.
	 *
	 * @param string $post_column The post data column.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array $post_data   The modified WP Post data array.
	 * @since 3.0.0
	 *
	 */
	protected function set_post_field_value( $post_column, $post_data ) {

		if ( is_array( $this->element->value ) ) {
			$this->element->value = implode( ', ', $this->element->value );
		}

		$value = $this->sanitize_post_field( $post_column, $this->element->value, $post_data );

		return $this->maybe_append( $post_column, $value, $post_data );
	}

	/**
	 * Sets the WP taxonomy terms based on the item config.
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array $post_data  The modified WP Post data array.
	 * @since 3.0.0
	 *
	 */
	protected function set_taxonomy_field_value( $taxonomy, $post_data ) {
		$terms = $this->get_element_terms( $taxonomy );
		$terms = array_filter( $terms );
		if ( ! empty( $terms ) ) {
			if ( 'category' === $taxonomy ) {
				$post_data['post_category'] = $terms;
			} else {
				$post_data['tax_input'][ $taxonomy ] = $terms;
			}
		}

		return $post_data;
	}

	/**
	 * Sets the WP meta data based on the item config.
	 *
	 * @param string $meta_key The meta key.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array  $post_data The modified WP Post data array.
	 * @since 3.0.0
	 *
	 */
	protected function set_meta_field_value( $meta_key, $post_data ) {
		$value = $this->sanitize_element_meta();
		if ( ! isset( $post_data['meta_input'] ) ) {
			$post_data['meta_input'] = array();
		}

		$post_data['meta_input'] = $this->maybe_append( $meta_key, $value, $post_data['meta_input'] );

		if ( 'attachment' === $this->element->type ) {
			$post_data = $this->set_media_field_value( $meta_key, $post_data );
		}

		return $post_data;
	}

	protected function set_database_field_value( $destination, $post_data ) {
		/**
		 * Update the post_data array to contain a 'database' array where each
		 * key is the `table.column` and the value is the content
		 * [...
		 *   "database" => ["table.column" => "Some great content from content workflow!"],
		 * ...]
		 */

		$post_data['database'][ $destination ] = $this->element->value;

		return $post_data;
	}

	/**
	 * Sets the WP media destination.
	 *
	 * @param string $destination The media destination.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array  $post_data   The modified WP Post data array.
	 * @since 3.0.0
	 *
	 */
	protected function set_media_field_value( $destination, $post_data ) {

		static $field_number = 0;
		$media_items = $this->sanitize_element_media();

		if (
			in_array( $destination, array( 'gallery', 'content_image', 'excerpt_image' ), true )
			&& is_array( $media_items )
		) {
			$field_number ++;
			$position = 0;
			foreach ( $media_items as $index => $media ) {
				$media_items[ $index ]->position     = ++ $position;
				$media_items[ $index ]->field_number = $field_number;

				$token = '#_gc_media_id_' . $media->id . '#';
				$field = 'excerpt_image' === $destination ? 'post_excerpt' : 'post_content';

				$post_data = $this->maybe_append( $field, $token, $post_data );
			}
		}

		$post_data['attachments'][] = array(
			'destination' => $destination,
			'media'       => $media_items,
		);

		return $post_data;
	}


	/**
	 * Sets the ACF field value in the post data.
	 *
	 * @param string $group_key The ACF group key.
	 * @param string $field_key The ACF field key.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array $post_data   The modified WP Post data array.
	 * @since 3.0.0
	 *
	 */

	protected function set_acf_field_value( $post_data ) {
		// We are not sure of the incoming post_ID so let's get the current post ID
		$post_id = $post_data['ID'];

		// Initialize an array to store updated post data
		$updated_post_data = $post_data;

		// Loop through the mapping data array
		foreach ( $this->mapping->data as $key => $value ) {
			// When it is a component, the key sandwiches '_component_' so let's get the key itself
			$content_key = explode( '_component_', $key )[0];

			// Check if the item has type wp-type-acf. We are assuming some items managed to skip the case check in the set_post_values function so we double check here.
			if ( isset( $value['type'] ) && $value['type'] === 'wp-type-acf' ) {

				// Fetch the corresponding value from $this->item->content
				$field_value = isset( $this->item->content->{$content_key} ) ? $this->item->content->{$content_key} : '';

				// Check if item has subfields. If it has then it is a component from GC
				if ( isset( $value['sub_fields'] ) ) {

					// Prepare the subfield data
					$subfield_keys = array();
					foreach ( $value['sub_fields'] as $sub_field_key ) {
						array_push( $subfield_keys, $sub_field_key );
					}
					// Let's ensure the repeater field is empty before adding rows
					delete_field( $value['field'], $post_id );

					// Let ACF add fields before rows. This order does some wonders and we need to revisit it.
					// update_field($value['field'], $component_row_data, $post_id);

					$row_index = 0;

					foreach ( $field_value as $subfield ) {

						$row_index ++;
						if ( ! is_object( $subfield ) ) {
							// When components are not set to be repeatable in GC we may end up getting non objects subfields.
							// Non objects like strings will fail for get_object_vars which expects objects
							// In such a situation the page breaks since the upload import can't be completed
							// We want to prevent the page break by just skipping import for such cases for now to give a better user experience
							// TODO: If it allowed that components can be non repeatable then we will need to handle the string case, else components always needs to be set repeatable even if it is going to be just one.
							continue;
						}
						// Check if the number of elements in $keys and $values match
						if ( count( $subfield_keys ) === count( get_object_vars( $subfield ) ) ) {
							// Combine the arrays if the counts match
							$component_row_data = array_combine( $subfield_keys, get_object_vars( $subfield ) );
						} else {
							// error_log("Number of keys and values don't match for array_combine()");
							// Skip the current iteration if component_row_data is not available
							continue;
						}
						// Convert object to associative array
						$component_row_data = json_decode( wp_json_encode( $component_row_data ), true );
						add_row( $value['field'], $component_row_data, $post_id );

						$subfield_key_id = - 1;

						foreach ( $subfield as $key => $subsubfield ) {
							$subfield_key_id ++;
							$item_key = $subfield_keys[ $subfield_key_id ];
							$item     = get_field_object( $item_key );

							if ( is_array( $subsubfield ) ) {

								if ( $item['parent'] ) {
									$parent_key = $item['parent'];
								}
								if ( $item['sub_fields'] ) {
									$children = array();
									foreach ( $item['sub_fields'] as $child ) {
										array_push( $children, $child['key'] );
									}
								}

								if ( $item['type'] && ( $item['type'] === 'checkbox' ) ) {
									$checkbox_labels = [];
									// Extract labels from each stdClass object and add them to the $labels array for checkboxes
									foreach ( $subsubfield as $checkbox ) {
										$checkbox_labels[] = $checkbox->label;
									}
								}

								foreach ( $subsubfield as $subsubfield_field ) {

									if ( $item['type'] == 'image' ) {
										$upload_dir = wp_upload_dir();
										$image_url  = $subsubfield_field->url;
										$image_data = wp_remote_get( $image_url );
										$filename   = $subsubfield_field->filename;

										// Check if the attachment already exists
										global $wpdb;
										$existing_attachment_id = $wpdb->get_var( $wpdb->prepare(
											"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title = %s",
											sanitize_file_name( $filename )
										) );

										if ( $existing_attachment_id ) {
											// If the attachment already exists, use its ID
											$attach_id = $existing_attachment_id;
										} else {
											if ( wp_mkdir_p( $upload_dir['path'] ) ) {
												$file = $upload_dir['path'] . '/' . $filename;
											} else {
												$file = $upload_dir['basedir'] . '/' . $filename;
											}

											require_once( ABSPATH . 'wp-admin/includes/file.php' );
											WP_Filesystem();
											global $wp_filesystem;
											$wp_filesystem->put_contents( $file, $image_data );

											$wp_filetype = wp_check_filetype( $filename, null );

											$attachment = array(
												'post_mime_type' => $wp_filetype['type'],
												'post_title'     => sanitize_file_name( $filename ),
												'post_content'   => '',
												'post_status'    => 'inherit'
											);

											$attach_id = wp_insert_attachment( $attachment, $file );
											require_once( ABSPATH . 'wp-admin/includes/image.php' );

											// Check if the attachment insertion was successful
											if ( ! is_wp_error( $attach_id ) ) {
												// Generate attachment metadata
												$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

												// Update attachment metadata
												wp_update_attachment_metadata( $attach_id, $attach_data );

											} else {
												// Log an error or handle the case where attachment insertion fails
											}
										}

										update_row( $parent_key, $row_index, [ $item_key => $attach_id ], $post_id );
										// Break out of the loop after processing the first image
										break;
									}


									if ( $item['type'] === 'repeater' ) {
										foreach ( $children as $child_key ) {
											// add_sub_row(['parent repeater', index, 'child repeater'], ['field_name' => $data], $post_id);
											add_sub_row( [
												$parent_key,
												$row_index,
												$item_key
											], [ $child_key => $subsubfield_field ], $post_id );
										}
									}

									if ( $item['type'] === 'checkbox' ) {
										update_row( $parent_key, $row_index, [ $item_key => $checkbox_labels ], $post_id );
									}

									if ( $item['type'] === 'radio' ) {
										update_row( $parent_key, $row_index, [ $item_key => $subsubfield_field->label ], $post_id );

									}

								}
							}
						}

					}

					$updated_post_data = $this->maybe_append( $value['field'], $field_value, $updated_post_data );

				} else {
					// If it's not a component, update the single ACF field normally
					$field_data  = array();
					$fields_data = array();
					if ( is_array( $field_value ) ) {
						foreach ( $field_value as $row_data ) {
							if ( ! is_object( $row_data ) ) {
								array_push( $field_data, $row_data );
							}
						}
						array_push( $fields_data, $field_data );
					}

					$field_key = $value['field'];

					// Get information about the field
					$field = get_field_object( $field_key );

					// Let's hope the field exists and is really not a component
					if ( $field ) {
						if ( ! empty( $field['sub_fields'] ) ) {
							$subsubfield_keys = array();
							foreach ( $field['sub_fields'] as $sub_field ) {
								array_push( $subsubfield_keys, $sub_field['key'] );
							}
						} else {
							// Field does not have subfields. We can possibly add some error handling
						}
					} else {
						// Field does not exist. We will decide what to do in that case later.
					}

					// This part can get more interesting if someone setup ACF fields of different structure than the structure in GC and maps them.
					// This might be a secondary case to look at, so we are keeping things in arrays so we can later just improve on it to handle those wild cases.

					$key_value_mapping = [];
					foreach ( $fields_data[0] as $value ) {
						// Assign each value to a sub-array with the key from subsubfield_keys
						$key_value_mapping[] = [ $subsubfield_keys[0] => $value ];
					}

					foreach ( $key_value_mapping as $key_value ) {
						add_row( $field_key, $key_value, $post_id );
					}
					update_field( $field_key, $key_value_mapping, $post_id );

					// lets do updated_post_data in a way that will work
					$updated_post_data = $this->maybe_append( $field_key, $key_value_mapping, $updated_post_data );

				}

			}
		}

		$updated_post_data['ID'] = $post_id;

		return $updated_post_data;
	}


	/**
	 * If field can append, then append the data, else set the data directly.
	 *
	 * @param string $field The field to set.
	 * @param mixed $value The value for the field.
	 * @param array $array The array to check against.
	 *
	 * @return array         The modified array.
	 * @since  3.0.0
	 *
	 */
	protected function maybe_append( $field, $value, $array ) {
		if ( $this->type_can_append( $field ) ) {
			$array[ $field ] = isset( $array[ $field ] ) ? $array[ $field ] : '';
			if ( 'gcinitial' === $array[ $field ] ) {
				$array[ $field ] = '';
			}
			$array[ $field ] .= $value;
		} else {
			$array[ $field ] = $value;
		}

		return $array;
	}

	/**
	 * Specific sanitization for WP post column fields.
	 *
	 * @param string $field The post field to sanitize.
	 * @param mixed $value The post field value to sanitize.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return mixed             The sanitized post field value.
	 * @throws Exception Will fail if the wrong kind of GC field is
	 *                      attempting to be sanitized.
	 *
	 * @since  3.0.0
	 *
	 */
	protected function sanitize_post_field( $field, $value, $post_data ) {
		if ( ! $value ) {
			return $value;
		}

		switch ( $field ) {
			case 'ID':
				throw new Exception( esc_html__( 'Cannot override post IDs', 'content-workflow-by-bynder' ), __LINE__ );

			case 'post_date':
			case 'post_date_gmt':
			case 'post_modified':
			case 'post_modified_gmt':
				if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
					throw new Exception( sprintf( esc_html__( '%s field requires a numeric timestamp, or date string.', 'content-workflow-by-bynder' ), esc_html( $field ) ), __LINE__ );
				}

				$value = is_numeric( $value ) ? $value : strtotime( $value );


				/**
				 * Ignoring as we need handling for data that aren't always GMT/UTC
				 */
				// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				return false !== strpos( $field, '_gmt' ) ? gmdate( 'Y-m-d H:i:s', $value ) : date( 'Y-m-d H:i:s', $value );
			case 'post_format':
				if ( isset( $post_data['post_type'] ) && ! post_type_supports( $post_data['post_type'], 'post-formats' ) ) {
					throw new Exception( sprintf( esc_html__( 'The %s post-type does not support post-formats.', 'content-workflow-by-bynder' ), esc_html( $post_data['post_type'] ) ), __LINE__ );
				}
			case 'post_title':
				$value = strip_tags( $value, '<strong><em><del><ins><code>' );
		}

		return sanitize_post_field( $field, $value, $post_data['ID'], 'db' );
	}

	/**
	 * Gets the terms from the current item element object.
	 *
	 * @param string $taxonomy The taxonomy to determine data storage method.
	 *
	 * @return mixed            The terms.
	 * @since  3.0.0
	 *
	 */
	protected function get_element_terms( $taxonomy ) {
		if ( 'text' === $this->element->type ) {
			$terms = array_map( 'trim', explode( ',', sanitize_text_field( $this->element->value ) ) );
		} elseif ( 'choice_checkbox' === $this->element->type ) {
			$terms = (array) ( is_string( $this->element->value ) ? json_decode( $this->element->value ) : $this->element->value );
		} else {
			$terms = (array) $this->element->value;
		}
		if ( ! empty( $terms ) && is_taxonomy_hierarchical( $taxonomy ) ) {
			foreach ( $terms as $key => $term ) {
				// @codingStandardsIgnoreStart
				if ( ! $term_info = term_exists( $term, $taxonomy ) ) {
					// @codingStandardsIgnoreEnd
					// Skip if a non-existent term ID is passed.
					if ( is_int( $term ) ) {
						unset( $terms[ $key ] );
						continue;
					}
					$term_info = wp_insert_term( $term, $taxonomy );
				}

				if ( ! is_wp_error( $term_info ) ) {
					$terms[ $key ] = $term_info['term_id'];
				}
			}
		}

		return apply_filters( 'cwby_get_element_terms', $terms, $this->element, $this->item );
	}

	/**
	 * Specific sanitization for the element value when stored as post-meta.
	 * Currently only filtered.
	 *
	 * @return mixed Value for meta.
	 * @since  3.0.0
	 *
	 */
	protected function sanitize_element_meta() {
		return apply_filters( 'cwby_sanitize_meta_field', $this->element->value, $this->element, $this->item );
	}

	/*
	 * Begin Media handling functionality.
	 */

	/**
	 * Specific sanitization for the element media.
	 * Currently only filtered.
	 *
	 * @return mixed Value for media.
	 * @since  3.0.0
	 *
	 */
	protected function sanitize_element_media() {
		return apply_filters( 'cwby_sanitize_media_field', $this->element->value, $this->element, $this->item );
	}

	/**
	 * After the post is created/updated, we sideload the applicable attachments,
	 * then we send the attachments to the requested location.
	 * (post content, excerpt post-meta, gallery, etc)
	 *
	 * @param array $attachments Array of attachments to sideload/attach/relocate.
	 * @param array $post_data The WP Post data array.
	 *
	 * @return array              Array of replacement key/values for strtr.
	 * @since  3.0.0
	 *
	 */
	protected function sideload_attachments( $attachments, $post_data ) {

		$post_id         = $post_data['ID'];
		$featured_img_id = false;

		$replacements = $gallery_ids = array();

		foreach ( $attachments as $attachment ) {
			if ( ! is_array( $attachment['media'] ) ) {
				continue;
			}

			foreach ( $attachment['media'] as $media ) {
				$attach_id = $this->maybe_sideload_file( $media, $post_id );

				if ( ! $attach_id || is_wp_error( $attach_id ) ) {
					// @todo How to handle failures?
					continue;
				}

				$token = '#_gc_media_id_' . $media->id . '#';

				if ( self::attachment_is_image( $attach_id ) ) {
					if ( 'featured_image' === $attachment['destination'] ) {
						$featured_img_id = $attach_id;
						break;
					} elseif ( in_array( $attachment['destination'], array(
						'content_image',
						'excerpt_image'
					), true ) ) {
						$field = 'excerpt_image' === $attachment['destination'] ? 'post_excerpt' : 'post_content';

						$atts  = array(
							'data-gcid' => $media->id,
							'class'     => "gathercontent-image attachment-full size-full wp-image-$attach_id",
						);
						$image = wp_get_attachment_image( $attach_id, 'full', false, $atts );

						// If we've found a GC "shortcode"...
						if ( $media_replace = $this->get_media_shortcode_attributes( $post_data[ $field ], (array) $media ) ) {

							foreach ( $media_replace as $replace_val => $atts ) {

								$maybe_image = ! empty( $atts )
									// Attempt to get requested image/link.
									? $this->get_requested_media( $atts, $media->id, $attach_id )
									: false;

								$img = $maybe_image ? $maybe_image : $image;

								// Replace the GC "shortcode" with the image/link.
								$img                                    = apply_filters( 'cwby_content_image', $img, $media, $attach_id, $post_data, $atts );
								$replacements[ $field ][ $replace_val ] = $img;
							}

							// The token should be removed from the content.
							$replacements[ $field ][ $token ] = '';
						} else {

							// Replace the token with the image.
							$image                            = apply_filters( 'cwby_content_image', $image, $media, $attach_id, $post_data, $atts );
							$replacements[ $field ][ $token ] = $image;
						}
					} elseif ( 'gallery' === $attachment['destination'] ) {

						$gallery_ids[] = $attach_id;
						$gallery_token = $token;

						// The token should be removed from the content.
						$replacements['post_content'][ $token ] = '';
					} else {
						$replacements['meta_input'][ $attachment['destination'] ][] = $attach_id;
					}
				} else { // NOT is_image
					if ( in_array( $attachment['destination'], array( 'content_image', 'excerpt_image' ), true ) ) {
						$field = 'excerpt_image' === $attachment['destination'] ? 'post_excerpt' : 'post_content';

						$atts = array(
							'data-gcid' => $media->id,
							'class'     => "gathercontent-file wp-file-$attach_id",
						);

						$link = '<a href="' . esc_url( wp_get_attachment_url( $attach_id ) ) . '" data-gcid="' . $atts['data-gcid'] . '" class="' . $atts['class'] . '">' . get_the_title( $attach_id ) . '</a>';

						// If we've found a GC "shortcode"...
						if ( $media_replace = $this->get_media_shortcode_attributes( $post_data[ $field ], (array) $media ) ) {

							foreach ( $media_replace as $replace_val => $atts ) {
								// Replace the GC "shortcode" with the file/link.
								$link                                   = apply_filters( 'cwby_content_file', $link, $media, $attach_id, $post_data, $atts );
								$replacements[ $field ][ $replace_val ] = $link;
							}

							// The token should be removed from the content.
							$replacements[ $field ][ $token ] = '';
						} else {

							// Replace the token with the image.
							$link                             = apply_filters( 'cwby_content_file', $link, $media, $attach_id, $post_data, $atts );
							$replacements[ $field ][ $token ] = $link;
						}
					} else {
						$replacements['meta_input'][ $attachment['destination'] ][] = $attach_id;
					}
				}

				// Store media item ID reference to attachment post-meta.
				\GatherContent\Importer\update_post_item_id( $attach_id, $media->id );
				\GatherContent\Importer\update_post_mapping_id( $attach_id, $this->mapping->ID );

				// Store other media item meta to attachment post-meta.
				\GatherContent\Importer\update_post_item_meta(
					$attach_id,
					array(
						'item_id'      => $this->item->id,
						'download_url' => $media->download_url,
						'url'          => $media->url,
						'filename'     => $media->filename,
						'file_id'      => $media->id,
						'size'         => $media->size,
						'alt_text'     => $media->alt_text,
						'created_at'   => isset( $media->created_at ) ? $media->created_at : $media->created_at,
						'updated_at'   => isset( $media->updated_at ) ? $media->updated_at : $media->updated_at,
					)
				);
				update_post_meta( $attach_id, '_wp_attachment_image_alt', $media->alt_text );
			}
		}

		if ( $featured_img_id ) {
			set_post_thumbnail( $post_id, $featured_img_id );
		}

		if ( ! empty( $gallery_ids ) ) {

			$shortcode = '[gallery link="file" size="full" ids="' . implode( ',', $gallery_ids ) . '"]';
			$shortcode = apply_filters( 'cwby_content_gallery_shortcode', $shortcode, $gallery_ids, $post_data );

			$replacements['post_content'][ $gallery_token ] = $shortcode;
		}

		return apply_filters( 'cwby_media_replacements', $replacements, $attachments, $post_data );
	}

	/**
	 * Handles determing if media from GC should be sideloaded, then sideloads.
	 *
	 * Logic is based on whether media already exists and if it has been updated.
	 *
	 * @param object $media The GC media object.
	 * @param int $post_id The post ID.
	 *
	 * @return int             The sideloaded attachment ID.
	 * @since  3.0.0
	 *
	 */
	protected function maybe_sideload_file( $media, $post_id ) {
		$attachment = \GatherContent\Importer\get_post_by_item_id( $media->id, array( 'post_status' => 'inherit' ) );

		if ( ! $attachment ) {
			return $this->sideload_file( $media->filename, $media->download_url, $post_id, $media->alt_text );
		}

		$attach_id = $attachment->ID;

		if ( $meta = \GatherContent\Importer\get_post_item_meta( $attach_id ) ) {

			$meta        = (object) $meta;
			$new_updated = strtotime( isset( $media->updated_at ) ? $media->updated_at : $media->updated_at );
			$old_updated = strtotime( $meta->updated_at );

			// Check if updated time-stamp is newer than previous updated timestamp.
			if ( $new_updated > $old_updated ) {

				$replace_data = apply_filters( 'cwby_replace_attachment_data_on_update', false, $attachment );

				// @todo How to handle failures?
				$attach_id = $this->sideload_and_update_attachment( $media->id, $media->filename, $media->download_url, $attachment, $replace_data, $media->alt_text );
			}
		}

		return $attach_id;
	}

	/**
	 * Downloads an image from the specified URL and attaches it to a post.
	 *
	 * @param string $file_name The Name of the image file.
	 * @param string $download_url The download URL of the image.
	 * @param int $post_id The post ID the media is to be associated with.
	 * @param string|null $alt_text Optional alt text to add to the image.
	 *
	 * @return string|WP_Error  Populated HTML img tag on success, WP_Error object otherwise.
	 */
	protected function sideload_file( $file_name, $download_url, $post_id, $alt_text = '' ) {
		if ( ! empty( $download_url ) ) {
			$file_array         = $this->tmp_file( $file_name, $download_url );
			$file_array['type'] = mime_content_type( $file_array['tmp_name'] );
			$extension          = '.' . ( new MimeTypes() )->getExtension( $file_array['type'] );
			$hasExtension       = substr( $file_array['name'], 0 - strlen( $extension ) ) === $extension;
			if ( ! $hasExtension ) {
				$file_array['name'] = $file_array['name'] . $extension;
			}

			if ( is_wp_error( $file_array ) ) {
				return $file_array;
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, $post_id );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				// @codingStandardsIgnoreStart
				@unlink( $file_array['tmp_name'] );

				// @codingStandardsIgnoreEnd
				return $id;
			}

			update_post_meta( $id, '_wp_attachment_image_alt', $alt_text );
			$src = wp_get_attachment_url( $id );
		}

		// Finally, check to make sure the file has been saved, then return the ID.
		return ! empty( $src ) ? $id : new WP_Error( 'image_sideload_failed' );
	}

	/**
	 * Handles re-sideloading attachment and replacing existing.
	 *
	 * @param string $file_name The file name.
	 * @param string $download_url The download url.
	 * @param object $attachment The attachment post object.
	 * @param bool $replace_data Whether to replace attachement title/content.
	 *                                   Default false.
	 * @param string|null $alt_text Optional alt text to add to the image.
	 *
	 * @return int                  The sideloaded attachment ID.
	 * @since  3.0.0
	 *
	 */
	protected function sideload_and_update_attachment( $file_name, $download_url, $attachment, $replace_data = false, $alt_text = '' ) {
		if ( ! isset( $attachment->ID ) || empty( $download_url ) ) {
			return new WP_Error( 'sideload_and_update_attachment_error' );
		}

		// @codingStandardsIgnoreStart
		// 5 minutes per image should be PLENTY.
		@set_time_limit( 900 );
		// @codingStandardsIgnoreEnd

		$time = substr( $attachment->post_date, 0, 4 ) > 0
			? $attachment->post_date
			: current_time( 'mysql' );

		$file_array = $this->tmp_file( $file_name, $download_url );

		$file = wp_handle_sideload( $file_array, array( 'test_form' => false ), $time );

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'upload_error', $file['error'] );
		}

		$args                   = (array) $attachment;
		$args['post_mime_type'] = $file['type'];

		$_file = $file['file'];

		if ( $replace_data ) {
			$title   = preg_replace( '/\.[^.]+$/', '', basename( $_file ) );
			$content = '';

			// Use image exif/iptc data for title and caption defaults if possible.
			// @codingStandardsIgnoreStart
			if ( $image_meta = @wp_read_image_metadata( $_file ) ) {
				// @codingStandardsIgnoreEnd
				if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
					$title = $image_meta['title'];
				}
				if ( trim( $image_meta['caption'] ) ) {
					$content = $image_meta['caption'];
				}
			}

			$args['post_title']   = $title;
			$args['post_content'] = $content;
		}

		// Save the attachment metadata.
		$id = wp_insert_attachment( $args, $_file, $attachment->post_parent );

		if ( is_wp_error( $id ) ) {
			// If error storing permanently, unlink.
			// @codingStandardsIgnoreStart
			@unlink( $file_array['tmp_name'] );
			// @codingStandardsIgnoreEnd
		} else {
			update_post_meta( $id, '_wp_attachment_image_alt', $alt_text );
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $_file ) );
		}

		return $id;
	}

	/**
	 * Download and create a temporary file.
	 *
	 * @param string $file_name The name of the file being downloaded.
	 * @param string $download_url The download URL of the file.
	 *
	 * @return array              The temporary file array.
	 * @since  3.0.0
	 *
	 */
	protected function tmp_file( $file_name, $download_url ) {

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$file_array = array();

		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $download_url );

		// If error storing temporarily, return the error.
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return $file_array['tmp_name'];
		}

		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file_name, $matches );

		$file_array['name'] = $matches ? basename( $matches[0] ) : basename( $file_name );

		return $file_array;
	}

	/**
	 * Checks an attachment's mime type to determine if it is an image.
	 *
	 * @param int $attach_id The attachement ID.
	 *
	 * @return bool
	 * @since  3.1.2
	 *
	 */
	public static function attachment_is_image( $attach_id ) {
		return preg_match( '~(jpe?g|jpe|gif|png|svg)\b~', get_post_mime_type( $attach_id ) );
	}

	/**
	 * wp_update_post wrapper that prevents a post revision.
	 *
	 * @param array $post_data Array of post data.
	 *
	 * @return int|WP_Error The value 0 or WP_Error on failure. The post ID on success.
	 * @since  3.0.2
	 *
	 */
	protected static function post_update_no_revision( $post_data ) {
		// Update post (but don't create a revision for it).
		remove_action( 'post_updated', 'wp_save_post_revision' );
		$post_id = wp_update_post( $post_data );
		add_action( 'post_updated', 'wp_save_post_revision' );

		return $post_id;
	}

	/**
	 * Determines if hierarchy mapping is supported for this post-type (or any).
	 * Defaults to `is_post_type_hierarchical` check.
	 * Can be overridden with 'gc_map_hierarchy' filter.
	 *
	 * @param string $post_type Post type to check if `is_post_type_hierarchical`
	 *
	 * @return bool              Whether post type supports hierarchy.
	 * @since  3.0.2
	 *
	 */
	public function should_map_hierarchy( $post_type ) {
		return apply_filters( 'cwby_map_hierarchy', is_post_type_hierarchical( $post_type ), $post_type, $this );
	}

	/**
	 * Add/create list of post/parent item ids to set WP hierarchy later.
	 *
	 * @param int $post_id WordPress post id to eventually update.
	 *
	 * @return void
	 * @since  3.0.2
	 *
	 */
	public function schedule_hierarchy_update( $post_id ) {

		$option = "cwby_associate_hierarchy_{$this->mapping->ID}";

		// Check we have existing pending hierchies to set.
		$pending = get_option( "gc_associate_hierarchy_{$this->mapping->ID}", array() );
		if ( empty( $pending ) || ! is_array( $pending ) ) {
			$pending = array( $post_id => $this->item->parent_id );
		} else {
			$pending[ $post_id ] = $this->item->parent_id;
		}

		// Update list of pending hierarchies for this mapping.
		update_option( "gc_associate_hierarchy_{$this->mapping->ID}", $pending, false );

		$args = array( $this->mapping->ID );

		// We'll want to restart our 'timer'.
		if ( wp_next_scheduled( 'cwby_associate_hierarchy', $args ) ) {
			wp_clear_scheduled_hook( 'cwby_associate_hierarchy', $args );
		}

		/*
		 * Schedule an event to associate hierarchy for these posts.
		 * Will likely never be hit, as the cwby_pull_complete event will take precedence.
		 */
		wp_schedule_single_event( time() + 60, 'cwby_associate_hierarchy', $args );
	}

	/**
	 * Hooked into cron event, loops through list of pending hierarchies for given mapping,
	 * and attempts to set the parent post id based on the parent GC item.
	 *
	 * @param int $mapping Mapping object or ID.
	 *
	 * @return void
	 * @since  3.0.2
	 *
	 */
	public static function associate_hierarchy( $mapping ) {
		$mapping = Mapping_Post::get( $mapping, true );
		if ( ! $mapping ) {
			return;
		}

		$mapping_id = $mapping->ID;

		$opt_name = "gc_associate_hierarchy_{$mapping_id}";
		$pending  = get_option( $opt_name, array() );

		if ( ! empty( $pending ) && is_array( $pending ) ) {
			foreach ( $pending as $post_id => $parent_item_id ) {
				$post = get_post( absint( $post_id ) );

				if ( ! $post ) {
					continue;
				}

				$parent_post = \GatherContent\Importer\get_post_by_item_id(
					$parent_item_id,
					array(
						'post_type' => $post->post_type,
					)
				);

				if ( ! $parent_post || ! isset( $parent_post->ID ) ) {
					continue;
				}

				// And update post (but don't create a revision for it).
				$post_id = self::post_update_no_revision(
					array(
						'ID'          => $post->ID,
						'post_parent' => absint( $parent_post->ID ),
					)
				);
			}
		}

		// We'll want to clear any scheduled events, since we completed them.
		if ( wp_next_scheduled( 'cwby_associate_hierarchy', array( $mapping_id ) ) ) {
			wp_clear_scheduled_hook( 'cwby_associate_hierarchy', array( $mapping_id ) );
		}

		return delete_option( $opt_name );
	}
}
