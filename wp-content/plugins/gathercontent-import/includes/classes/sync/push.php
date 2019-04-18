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
	 * @since 3.0.0
	 *
	 * @param API $api API object.
	 */
	public function __construct( API $api ) {
		parent::__construct( $api, new Async_Push_Action() );
	}

	/**
	 * Initiate admin hooks
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function init_hooks() {
		parent::init_hooks();
		add_action( 'wp_async_gc_push_items', array( $this, 'sync_items' ) );
		add_action( 'wp_async_nopriv_gc_push_items', array( $this, 'sync_items' ) );
	}

	/**
	 * A method for trying to push directly (without async hooks).
	 *
	 * @since  3.0.0
	 *
	 * @param  int $mapping_post_id Mapping post ID.
	 *
	 * @return mixed Result of push. WP_Error on failure.
	 */
	public function maybe_push_item( $mapping_post_id ) {
		try {

			$post = $this->get_post( $mapping_post_id );
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
	 * @since  3.0.0
	 *
	 * @param  int $id WP post ID.
	 *
	 * @throws Exception On failure.
	 *
	 * @return mixed Result of push.
	 */
	protected function do_item( $id ) {
		$this->post = $this->get_post( $id );

		$this->check_mapping_data( $this->mapping );

		$this->set_item( \GatherContent\Importer\get_post_item_id( $this->post->ID ) );

		$config_update = $this->map_wp_data_to_gc_data();

		// No updated data, so bail.
		if ( empty( $config_update ) ) {
			throw new Exception( sprintf( __( 'No update data found for that post ID: %d', 'gathercontent-import' ), $this->post->ID ), __LINE__, array(
				'post_id'    => $this->post->ID,
				'mapping_id' => $this->mapping->ID,
				'item_id'    => $this->item->id,
			) );
		}

		// If we found updates, do the update.
		return $this->maybe_do_item_update( $config_update );
	}

	private function get_structured_array( $config ) {
		$structured_content = [];

		foreach ( $config as $tab ) {
			foreach ( $tab->elements as $element ) {
				switch ( $element->type ) {
					case 'text':
						$structured_content[ $element->name ] = $element->value;
						break;
					case 'choice_radio':
						$selected_radios = array();
						foreach ( $element->options as $option ) {
							if ( $option->selected ) {
								$radio = array(
									'id' => $option->name,
								);

								if ( $option->value ) {
									$radio['value'] = $option->value;
								}
								$selected_radios[] = $radio;
							}
						}

						$structured_content[ $element->name ] = $selected_radios;
						break;
					case 'choice_checkbox':
						$selected_checkboxes = array();
						foreach ( $element->options as $option ) {
							if ( $option->selected ) {
								$selected_checkboxes[] = array(
									'id' => $option->name,
								);;
							}
						}

						$structured_content[ $element->name ] = $selected_checkboxes;
						break;
				}
			}
		}

		return $structured_content;
	}

	/**
	 * Pushes WP post to GC.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $update The item config update delta array.
	 *
	 * @throws Exception On failure.
	 *
	 * @return mixed Result of push.
	 */
	public function maybe_do_item_update( $update ) {
		// Get our initial config reference.
		$config = json_decode( $this->config );

		// And update it with the new values.
		foreach ( $update as $index => $tab ) {
			foreach ( $tab->elements as $element_index => $element ) {
				$config[ $index ]->elements[ $element_index ] = $element;
			}
		}


		if ( ! $this->mapping->data( 'structure_uuid' ) ) {

			if ( $this->item_id ) {
				$result = $this->api->save_item( $this->item_id, $config );
			} else {
				$result = $this->api->create_item(
					$this->mapping->get_project(),
					$this->mapping->get_template(),
					$this->post->post_title,
					$config
				);
			}
		} else {
			$content = $this->get_structured_array( $config );

			if ( $this->item_id ) {
				$result = $this->api->update_item( $this->item_id, $content );
			} else {
				$result = $this->api->create_structured_item(
					$this->mapping->get_project(),
					$this->mapping->get_template(),
					$this->post->post_title,
					$content
				);
			}
		}

		if ( $result && ! is_wp_error( $result ) ) {
			if ( ! $this->item_id ) {
				\GatherContent\Importer\update_post_item_id( $this->post->ID, $result );
				$this->item_id = $result;
			}

			// If item update was successful, re-fetch it from the API...
			$this->item = $this->api->uncached()->get_item( $this->item_id );

			// and update the meta.
			\GatherContent\Importer\update_post_item_meta( $this->post->ID, array(
				'created_at' => $this->item->created_at->date,
				'updated_at' => $this->item->updated_at->date,
			) );
		}

		return $result;
	}

	/**
	 * Sets the item to be pushed to. If it doesn't exist yet, we create it now.
	 *
	 * @since 3.0.0
	 *
	 * @param integer $item_id Item id.
	 *
	 * @throws Exception On failure.
	 *
	 * @return $item
	 */
	protected function set_item( $item_id ) {
		$this->item_id = $item_id;

		if ( ! $item_id ) {
			$item = $this->api->get_template( $this->mapping->get_template() );
		} else {
			$item = parent::set_item( $item_id );
		}

		$this->item_config = $item->config;

		$this->config = wp_json_encode( $item->config );

	}

	/**
	 * Maps the WP post data to the GC item config.
	 *
	 * @since  3.0.0
	 *
	 * @return array Item config array on success.
	 */
	protected function map_wp_data_to_gc_data() {
		$config = $this->loop_item_elements_and_map();
		return apply_filters( 'gc_update_gc_config_data', $config, $this );
	}

	/**
	 * Loops the GC item config elements and maps the WP post data.
	 *
	 * @since  3.0.0
	 *
	 * @return array Modified item config array on success.
	 */
	public function loop_item_elements_and_map() {
		if ( empty( $this->item_config ) ) {
			return false;
		}

		foreach ( $this->item_config as $index => $tab ) {
			if ( ! isset( $tab->elements ) || ! $tab->elements ) {
				continue;
			}

			foreach ( $tab->elements as $element_index => $this->element ) {
				if ( ! empty( $this->element->value ) ) {
					$this->element->value = self::remove_zero_width( $this->element->value );
				}

				$source = $this->mapping->data( $this->element->name );
				$source_type = isset( $source['type'] ) ? $source['type'] : '';
				$source_key = isset( $source['value'] ) ? $source['value'] : '';

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
					! $source
					|| ! isset( $source['type'], $source['value'] )
					|| ! $this->set_values_from_wp( $source_type, $source_key )
				) {
					unset( $this->item_config[ $index ]->elements[ $element_index ] );
				}
			}

			if ( empty( $this->item_config[ $index ]->elements ) ) {
				unset( $this->item_config[ $index ] );
			}
		}

		$this->remove_unknowns();

		return $this->item_config;
	}

	/**
	 * Loops the $done array and looks for duplicates (unknowns) and removes them.
	 *
	 * @todo Fix this. Probably need a reverse mapping UI for each item push, or something.
	 *
	 * @since  3.0.0
	 *
	 * @return void
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
	 * @since 3.0.0
	 *
	 * @param string $source_type The data source type.
	 * @param string $source_key  The data source key.
	 *
	 * @return array $updated Whether value was updated.
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

			/*
			 * @todo determine if GC can accept file updates.
			 * case 'wp-type-media':
			 * 	$updated = $this->get_media_field_value( $source_key );
			 * 	break;
			 */
		}

		return $updated;
	}

	/**
	 * Sets the item config element value for WP post fields,
	 * if it is determeined that the value changed.
	 *
	 * @since 3.0.0
	 *
	 * @param string $post_column The post data column.
	 *
	 * @return bool $updated Whether value was updated.
	 */
	protected function set_post_field_value( $post_column ) {
		$updated = false;
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
				$value = $this->convert_media_to_shortcodes( $value );
				if ( 'post_content' === $post_column ) {
					$value = apply_filters( 'the_content', $value );
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
			$updated = true;
		}

		return $updated;
	}

	/**
	 * Sets the item config element value for WP taxonomy terms,
	 * if it is determeined that the value changed.
	 *
	 * @since 3.0.0
	 *
	 * @param string $taxonomy The taxonomy name.
	 *
	 * @return bool $updated Whether value was updated.
	 */
	protected function set_taxonomy_field_value( $taxonomy ) {
		$terms      = get_the_terms( $this->post, $taxonomy );
		$term_names = ! is_wp_error( $terms ) && ! empty( $terms )
			? wp_list_pluck( $terms, 'name' )
			: array();

		$updated = $this->set_taxonomy_field_value_from_names( $term_names );

		return apply_filters( 'gc_config_taxonomy_field_value_updated', $updated, $taxonomy, $terms, $this );
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
					$updated = true;
				}
				break;

			case 'choice_checkbox':
			case 'choice_radio':
				$updated = $this->update_element_selected_options( function( $label ) use ( $term_names ) {
					return in_array( $label, $term_names, true );
				} );

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
	 * @since 3.0.0
	 *
	 * @param string $meta_key The meta key.
	 *
	 * @return bool $updated Whether value was updated.
	 */
	protected function set_meta_field_value( $meta_key ) {
		$updated = false;
		$meta_value = get_post_meta( $this->post->ID, $meta_key, 1 );

		$check = apply_filters( 'gc_config_pre_meta_field_value_updated', null, $meta_value, $meta_key, $this );
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
					$updated = true;
				}
				break;

			case 'choice_radio':
				$updated = $this->update_element_selected_options( function( $label ) use ( $meta_value ) {
					return $meta_value === $label;
				} );
				break;

			case 'choice_checkbox':

				if ( empty( $meta_value ) ) {
					$meta_value = array();
				} else {
					$meta_value = is_array( $meta_value ) ? $meta_value : array( $meta_value );
				}

				$updated = $this->update_element_selected_options( function( $label ) use ( $meta_value ) {
					return in_array( $label, $meta_value, true );
				} );
				break;

		}

		return apply_filters( 'gc_config_meta_field_value_updated', $updated, $meta_value, $meta_key, $this );
	}

	/**
	 * Uses $callback to determine if each option value should be selected,
	 *
	 * @since  3.0.0
	 *
	 * @param  callable $callback Closure.
	 *
	 * @return bool            	Whether the options were updated or not.
	 */
	public function update_element_selected_options( $callback ) {
		$pre_options = wp_json_encode( $this->element->options );

		$last_key = false;
		if ( isset( $this->element->other_option ) && $this->element->other_option ) {
			$keys = array_keys( $this->element->options );
			$last_key = end( $keys );
		}

		foreach ( $this->element->options as $key => $option ) {

			// If it's the "Other" option, we need to use the option's value, not label.
			$label = $last_key === $key && isset( $option->value )
				? $option->value
				: $option->label;

			if ( $callback( self::remove_zero_width( $label ) ) ) {
				$this->element->options[ $key ]->selected = true;
			} else {
				$this->element->options[ $key ]->selected = false;

				// Else GC API error:
				// "Other option value must be empty when other option not selected".
				if ( $last_key === $key ) {
					$this->element->options[ $key ]->value = '';
				}
			}
		}

		$post_options = wp_json_encode( $this->element->options );

		// @codingStandardsIgnoreStart
		// Check if the values have been updated.
		// We don't necessarily want strict comparison here.
		return $pre_options != $post_options;
		// @codingStandardsIgnoreEnd
	}

}
