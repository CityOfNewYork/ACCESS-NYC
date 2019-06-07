<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Sync;
use GatherContent\Importer\Mapping_Post;
use GatherContent\Importer\API;
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
	 * @since 3.0.0
	 *
	 * @param API $api API object.
	 */
	public function __construct( API $api ) {
		parent::__construct( $api, new Async_Pull_Action() );
	}

	/**
	 * Initiate plugins_loaded hooks.
	 *
	 * @since  3.0.2
	 *
	 * @return void
	 */
	public static function init_plugins_loaded_hooks() {
		add_action( 'gc_associate_hierarchy', array( __CLASS__, 'associate_hierarchy' ) );
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
		add_action( 'wp_async_gc_pull_items', array( $this, 'sync_items' ) );
		add_action( 'wp_async_nopriv_gc_pull_items', array( $this, 'sync_items' ) );
		add_action( 'gc_pull_complete', array( __CLASS__, 'associate_hierarchy' ) );
	}

	/**
	 * A method for trying to pull directly (without async hooks).
	 *
	 * @since  3.0.0
	 *
	 * @param  int $mapping_post Mapping post ID or object.
	 * @param  int $item_id      GC item id.
	 *
	 * @return mixed Result of pull. WP_Error on failure.
	 */
	public function maybe_pull_item( $mapping_post, $item_id ) {
		try {
			$this->mapping = Mapping_Post::get( $mapping_post, true );
			$result = $this->do_item( $item_id );
		} catch ( \Exception $e ) {
			$result = new WP_Error( 'gc_pull_item_fail_' . $e->getCode(), $e->getMessage(), $e->get_data() );
		}

		return $result;
	}

	/**
	 * Pulls GC item to update a post after some sanitiy checks.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $id GC Item ID.
	 *
	 * @throws Exception On failure.
	 *
	 * @return mixed Result of pull.
	 */
	protected function do_item( $id ) {
		$this->check_mapping_data( $this->mapping );

		$this->set_item( $id );

		$post_data = array();
		$attachments = $tax_terms = false;

		if ( $existing = \GatherContent\Importer\get_post_by_item_id( $id ) ) {
			// Check if item is up-to-date and if pull is necessary.
			$meta = \GatherContent\Importer\get_post_item_meta( $existing->ID );
			$updated_at = isset( $meta['updated_at'] ) ? $meta['updated_at'] : 0;
			$updated_at = is_object( $updated_at ) ? $updated_at->date : $updated_at;

			if (
				// Check if we have updated_at values to compare.
				isset( $this->item->updated_at ) && ! empty( $updated_at )
				// And if we do, compare them to see if GC item is newer.
				&& ( $is_up_to_date = strtotime( $this->item->updated_at->date ) <= strtotime( $updated_at ) )
				// If it's not newer, then don't update (unless asked to via filter).
				&& $is_up_to_date && apply_filters( 'gc_only_update_if_newer', true )
			) {
				throw new Exception( sprintf( __( 'WordPress has most recent changes for %1$s (Item ID: %2$d):', 'gathercontent-import' ), $this->item->name, $this->item->id ), __LINE__, array( 'post' => $existing->ID, 'item' => $this->item->id ) );
			}

			$post_data = (array) $existing;
		} else {
			$post_data['ID'] = 0;
		}

		$post_data = $this->map_gc_data_to_wp_data( $post_data );

		if ( ! empty( $post_data['attachments'] ) ) {
			$attachments = $post_data['attachments'];
			unset( $post_data['attachments'] );
		}

		if ( ! empty( $post_data['tax_input'] ) ) {
			$tax_terms = $post_data['tax_input'];
			unset( $post_data['tax_input'] );
		}

		$post_id = wp_insert_post( $post_data, 1 );

		if ( is_wp_error( $post_id ) ) {
			throw new Exception( $post_id->get_error_message(), __LINE__, $post_id->get_error_data() );
		}

		$post_data['ID'] = $post_id;

		// Store item ID reference to post-meta.
		\GatherContent\Importer\update_post_item_id( $post_id, $id );
		\GatherContent\Importer\update_post_mapping_id( $post_id, $this->mapping->ID );
		\GatherContent\Importer\update_post_item_meta( $post_id, array(
			'created_at' => $this->item->created_at->date,
			'updated_at' => $this->item->updated_at->date,
		) );

		if ( ! empty( $tax_terms ) ) {
			foreach ( $tax_terms as $taxonomy => $terms ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( ! $taxonomy_obj ) {
					/* translators: %s: taxonomy name */
					_doing_it_wrong( __FUNCTION__, sprintf( __( 'Invalid taxonomy: %s.' ), $taxonomy ), '4.4.0' );
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
			$attachments = apply_filters( 'gc_media_objects', $attachments, $post_data );
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
					$updated_post_data['meta_input'] = array_map( function( $meta ) {
						return is_array( $meta ) && count( $meta ) > 1
							? $meta
							: array_shift( $meta );
					}, $replacements['meta_input'] );
				}
			}
		}

		/*// Check if we need to set hierarchies.
		if ( $this->should_map_hierarchy( $post_data['post_type'] ) && isset( $this->item->parent_id ) && $this->item->parent_id ) {

			// Check if an associated WordPress item exists for the parent item id.
			$parent_post = \GatherContent\Importer\get_post_by_item_id( absint( $this->item->parent_id ), array(
				'post_type' => $post_data['post_type'],
			) );

			// If so, we'll go ahead and update the post_parent value.
			if ( $parent_post && isset( $parent_post->ID ) ) {
				$updated_post_data['post_parent'] = absint( $parent_post->ID );
			}
			// Otherwise, let's save this to the array of IDs needed to be checked later (let the import finish).
			else {
				$this->schedule_hierarchy_update( $post_id );
			}
		}*/

		if ( ! empty( $updated_post_data ) ) {
			// And update post (but don't create a revision for it).
			self::post_update_no_revision( array_merge( $post_data, $updated_post_data ) );
		}

		if ( $status = $this->mapping->get_item_new_status( $this->item ) ) {
			// Update the GC item status.
			$this->api->set_item_status( $id, $status );
		}

		return $post_id;
	}

	/**
	 * Maps the GC item config data to WP data.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $post_data The WP Post data array.
	 *
	 * @return array Item config array on success.
	 */
	protected function map_gc_data_to_wp_data( $post_data = array() ) {
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
			$backup[ $key ] = isset( $post_data[ $key ] ) ? $post_data[ $key ] : '';
			$post_data[ $key ] = 'gcinitial';
		}

		$files = $this->api->uncached()->get_item_files( $this->item->id );

		$this->item->files = array();
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$this->item->files[ $file->field ][] = $file;
			}
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
			$post_data = apply_filters( 'gc_update_wp_post_data', $post_data, $this );
		} else {
			$post_data = apply_filters( 'gc_new_wp_post_data', $post_data, $this );
		}

		return $post_data;
	}

	/**
	 * Check if post title should be updated with item title.
	 *
	 * Only if the post title is empty, or there is no post_title field mapped.
	 *
	 * @since  3.1.8
	 *
	 * @return boolean
	 */
	public function should_update_title_with_item_name( $post_data ) {
		$should = ! empty( $this->item->name );
		$empty_title = empty( $post_data['post_title'] );
		if ( ! $empty_title ) {
			$should = ! $this->has_post_title_mapping();
		}

		return $should;
	}

	/**
	 * Check if mapping has a mapping for the post_title. (To fallback to item title)
	 *
	 * @since  3.1.8
	 *
	 * @return boolean
	 */
	public function has_post_title_mapping() {
		try {
			array_filter( $this->mapping->data(), function( $mapped ) {
				if ( isset( $mapped['type'], $mapped['value'] )
					&& 'wp-type-post' === $mapped['type']
					&& 'post_title' === $mapped['value'] ) {
					throw new \Exception( 'found' );
				}
			} );
		} catch ( \Exception $e ) {
			return true;
		}
		return false;
	}

	/**
	 * Loops the GC item config elements and maps the WP post data.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $post_data The WP Post data array.
	 *
	 * @return array Modified post data array on success.
	 */
	protected function loop_item_elements_and_map( $post_data ) {
		if ( ! isset( $this->item->config ) || empty( $this->item->config ) ) {
			return $post_data;
		}

		$columns = [];

		foreach ( $this->item->config as $tab ) {
			if ( ! isset( $tab->elements ) || ! $tab->elements ) {
				continue;
			}

			foreach ( $tab->elements as $this->element ) {
				$destination = $this->mapping->data( $this->element->name );
				if ( $destination && isset( $destination['type'], $destination['value'] ) ) {
					$columns[ $destination['value'] ] = true;
					$post_data = $this->set_post_values( $destination, $post_data );
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
	 * @since 3.0.0
	 *
	 * @param  array $destination Destination array, includes type and value.
	 * @param  array $post_data   The WP Post data array.
	 *
	 * @return array $post_data   The modified WP Post data array.
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
	 * @since 3.0.0
	 *
	 * @param  string $post_column The post data column.
	 * @param  array  $post_data   The WP Post data array.
	 *
	 * @return array $post_data   The modified WP Post data array.
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
	 * @since 3.0.0
	 *
	 * @param  string $taxonomy  The taxonomy name.
	 * @param  array  $post_data The WP Post data array.
	 *
	 * @return array $post_data  The modified WP Post data array.
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
	 * @since 3.0.0
	 *
	 * @param  string $meta_key  The meta key.
	 * @param  array  $post_data The WP Post data array.
	 *
	 * @return array  $post_data The modified WP Post data array.
	 */
	protected function set_meta_field_value( $meta_key, $post_data ) {
		$value = $this->sanitize_element_meta();
		if ( ! isset( $post_data['meta_input'] ) ) {
			$post_data['meta_input'] = array();
		}

		$post_data['meta_input'] = $this->maybe_append( $meta_key, $value, $post_data['meta_input'] );

		if ( 'files' === $this->element->type ) {
			$post_data = $this->set_media_field_value( $meta_key, $post_data );
		}

		return $post_data;
	}

	/**
	 * Sets the WP media destination.
	 *
	 * @since 3.0.0
	 *
	 * @param  string $destination The media destination.
	 * @param  array  $post_data   The WP Post data array.
	 *
	 * @return array  $post_data   The modified WP Post data array.
	 */
	protected function set_media_field_value( $destination, $post_data ) {
		static $field_number = 0;
		$media_items = $this->sanitize_element_media();

		if (
			in_array( $destination, array( 'gallery', 'content_image', 'excerpt_image' ), true )
			&& is_array( $media_items )
		) {
			$field_number++;
			$position = 0;
			foreach ( $media_items as $index => $media ) {
				$media_items[ $index ]->position = ++$position;
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
	 * If field can append, then append the data, else set the data directly.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $field The field to set.
	 * @param  mixed  $value The value for the field.
	 * @param  array  $array The array to check against.
	 *
	 * @return array         The modified array.
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
	 * @since  3.0.0
	 *
	 * @param  string $field     The post field to sanitize.
	 * @param  mixed  $value     The post field value to sanitize.
	 * @param  array  $post_data The WP Post data array.
	 *
	 * @throws Exception Will fail if the wrong kind of GC field is
	 *         				attempting to be sanitized.
	 *
	 * @return mixed             The sanitized post field value.
	 */
	protected function sanitize_post_field( $field, $value, $post_data ) {
		if ( ! $value ) {
			return $value;
		}

		switch ( $field ) {
			case 'ID':
				throw new Exception( __( 'Cannot override post IDs', 'gathercontent-import' ), __LINE__ );

			case 'post_date':
			case 'post_date_gmt':
			case 'post_modified':
			case 'post_modified_gmt':
				if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
					throw new Exception( sprintf( __( '%s field requires a numeric timestamp, or date string.', 'gathercontent-import' ), $field ), __LINE__ );
				}

				$value = is_numeric( $value ) ? $value : strtotime( $value );

				return false !== strpos( $field, '_gmt' )
					? gmdate( 'Y-m-d H:i:s', $value )
					: date( 'Y-m-d H:i:s', $value );
			case 'post_format':
				if ( isset( $post_data['post_type'] ) && ! post_type_supports( $post_data['post_type'], 'post-formats' ) ) {
					throw new Exception( sprintf( __( 'The %s post-type does not support post-formats.', 'gathercontent-import' ), $post_data['post_type'] ), __LINE__ );
				}
			case 'post_title':
				$value = strip_tags( $value, '<strong><em><del><ins><code>' );

		}

		return sanitize_post_field( $field, $value, $post_data['ID'], 'db' );
	}

	/**
	 * Gets the terms from the current item element object.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $taxonomy The taxonomy to determine data storage method.
	 *
	 * @return mixed            The terms.
	 */
	protected function get_element_terms( $taxonomy ) {
		if ( 'text' === $this->element->type ) {
			$terms = array_map( 'trim', explode( ',', sanitize_text_field( $this->element->value ) ) );
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

		return apply_filters( 'gc_get_element_terms', $terms, $this->element, $this->item );
	}

	/**
	 * Specific sanitization for the element value when stored as post-meta.
	 * Currently only filtered.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed Value for meta.
	 */
	protected function sanitize_element_meta() {
		return apply_filters( 'gc_sanitize_meta_field', $this->element->value, $this->element, $this->item );
	}

	/*
	 * Begin Media handling functionality.
	 */

	/**
	 * Specific sanitization for the element media.
	 * Currently only filtered.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed Value for media.
	 */
	protected function sanitize_element_media() {
		return apply_filters( 'gc_sanitize_media_field', $this->element->value, $this->element, $this->item );
	}

	/**
	 * After the post is created/updated, we sideload the applicable attachments,
	 * then we send the attachments to the requested location.
	 * (post content, excerpt post-meta, gallery, etc)
	 *
	 * @since  3.0.0
	 *
	 * @param  array $attachments Array of attachments to sideload/attach/relocate.
	 * @param  array $post_data   The WP Post data array.
	 *
	 * @return array              Array of replacement key/values for strtr.
	 */
	protected function sideload_attachments( $attachments, $post_data ) {
		$post_id = $post_data['ID'];
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
					} elseif ( in_array( $attachment['destination'], array( 'content_image', 'excerpt_image' ), true ) ) {
						$field = 'excerpt_image' === $attachment['destination'] ? 'post_excerpt' : 'post_content';

						$atts = array(
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
								$img = apply_filters( 'gc_content_image', $img, $media, $attach_id, $post_data, $atts );
								$replacements[ $field ][ $replace_val ] = $img;
							}

							// The token should be removed from the content.
							$replacements[ $field ][ $token ] = '';

						} else {

							// Replace the token with the image.
							$image = apply_filters( 'gc_content_image', $image, $media, $attach_id, $post_data, $atts );
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

						$link = '<a href="' . esc_url( wp_get_attachment_url( $attach_id ) ) . '" data-gcid="' . $atts['data-gcid']. '" class="' . $atts['class'] . '">' . get_the_title( $attach_id  ) . '</a>';

						// If we've found a GC "shortcode"...
						if ( $media_replace = $this->get_media_shortcode_attributes( $post_data[ $field ], (array) $media ) ) {

							foreach ( $media_replace as $replace_val => $atts ) {
								// Replace the GC "shortcode" with the file/link.
								$link = apply_filters( 'gc_content_file', $link, $media, $attach_id, $post_data, $atts );
								$replacements[ $field ][ $replace_val ] = $link;
							}

							// The token should be removed from the content.
							$replacements[ $field ][ $token ] = '';

						} else {

							// Replace the token with the image.
							$link = apply_filters( 'gc_content_file', $link, $media, $attach_id, $post_data, $atts );
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
				\GatherContent\Importer\update_post_item_meta( $attach_id, array(
					'user_id'    => $media->user_id,
					'item_id'    => $media->item_id,
					'field'      => $media->field,
					'type'       => $media->type,
					'url'        => $media->url,
					'filename'   => $media->filename,
					'size'       => $media->size,
					'created_at' => isset( $media->created_at->date ) ? $media->created_at->date : $media->created_at,
					'updated_at' => isset( $media->updated_at->date ) ? $media->updated_at->date : $media->updated_at,
				) );
			}
		}

		if ( $featured_img_id ) {
			set_post_thumbnail( $post_id, $featured_img_id );
		}

		if ( ! empty( $gallery_ids ) ) {

			$shortcode = '[gallery link="file" size="full" ids="' . implode( ',', $gallery_ids ) . '"]';
			$shortcode = apply_filters( 'gc_content_gallery_shortcode', $shortcode, $gallery_ids, $post_data );

			$replacements['post_content'][ $gallery_token ] = $shortcode;
		}

		return apply_filters( 'gc_media_replacements', $replacements, $attachments, $post_data );
	}

	/**
	 * Handles determing if media from GC should be sideloaded, then sideloads.
	 *
	 * Logic is based on whether media already exists and if it has been updated.
	 *
	 * @since  3.0.0
	 *
	 * @param  object $media   The GC media object.
	 * @param  int    $post_id The post ID.
	 *
	 * @return int             The sideloaded attachment ID.
	 */
	protected function maybe_sideload_file( $media, $post_id ) {
		$attachment = \GatherContent\Importer\get_post_by_item_id( $media->id, array( 'post_status' => 'inherit' ) );

		if ( ! $attachment ) {
			return $this->sideload_file( $media->id, $media->filename, $post_id );
		}

		$attach_id = $attachment->ID;

		if ( $meta = \GatherContent\Importer\get_post_item_meta( $attach_id ) ) {

			$meta = (object) $meta;
			$new_updated = strtotime( isset( $media->updated_at->date ) ? $media->updated_at->date : $media->updated_at );
			$old_updated = strtotime( $meta->updated_at );

			// Check if updated time-stamp is newer than previous updated timestamp.
			if ( $new_updated > $old_updated ) {

				$replace_data = apply_filters( 'gc_replace_attachment_data_on_update', false, $attachment );

				// @todo How to handle failures?
				$attach_id = $this->sideload_and_update_attachment( $media->id, $media->filename, $attachment, $replace_data );
			}
		}

		return $attach_id;
	}

	/**
	 * Downloads an image from the specified URL and attaches it to a post.
	 *
	 * @param string $file_url  The URL of the image to download.
	 * @param string $file_name The Name of the image file.
	 * @param int    $post_id   The post ID the media is to be associated with.
	 * @return string|WP_Error  Populated HTML img tag on success, WP_Error object otherwise.
	 */
	protected function sideload_file( $file_url, $file_name, $post_id ) {
		if ( ! empty( $file_url ) ) {
			$file_array = $this->tmp_file( $file_url, $file_name );

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

			$src = wp_get_attachment_url( $id );
		}

		// Finally, check to make sure the file has been saved, then return the ID.
		return ! empty( $src ) ? $id : new WP_Error( 'image_sideload_failed' );
	}

	/**
	 * Handles re-sideloading attachment and replacing existing.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $file_url     The file URL.
	 * @param  string $file_name    The file name.
	 * @param  object $attachment   The attachment post object.
	 * @param  bool   $replace_data Whether to replace attachement title/content.
	 *                              Default false.
	 *
	 * @return int                  The sideloaded attachment ID.
	 */
	protected function sideload_and_update_attachment( $file_url, $file_name, $attachment, $replace_data = false ) {
		if ( ! isset( $attachment->ID ) || empty( $file_url ) ) {
			return new WP_Error( 'sideload_and_update_attachment_error' );
		}

		// @codingStandardsIgnoreStart
		// 5 minutes per image should be PLENTY.
		@set_time_limit( 900 );
		// @codingStandardsIgnoreEnd

		$time = substr( $attachment->post_date, 0, 4 ) > 0
			? $attachment->post_date
			: current_time( 'mysql' );

		$file_array = $this->tmp_file( $file_url, $file_name );
		$file       = wp_handle_sideload( $file_array, array( 'test_form' => false ), $time );

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'upload_error', $file['error'] );
		}

		$args = (array) $attachment;
		$args['post_mime_type'] = $file['type'];

		$_file = $file['file'];

		if ( $replace_data ) {
			$title = preg_replace( '/\.[^.]+$/', '', basename( $_file ) );
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

			$args['post_title'] = $title;
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
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $_file ) );
		}

		return $id;
	}

	/**
	 * Download and create a temporary file.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $file_id  The file to download.
	 * @param  string $file_name The name of the file being downloaded.
	 *
	 * @return array              The temporary file array.
	 */
	protected function tmp_file( $file_id, $file_name ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		require_once( ABSPATH . '/wp-admin/includes/media.php' );
		require_once( ABSPATH . '/wp-admin/includes/image.php' );

		$file_array = array();

		// Download file to temp location.
		$file_array['tmp_name'] = $this->api->get_file( $file_id );

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
	 * @since  3.1.2
	 *
	 * @param  int  $attach_id The attachement ID.
	 *
	 * @return bool
	 */
	public static function attachment_is_image( $attach_id ) {
		return preg_match( '~(jpe?g|jpe|gif|png|svg)\b~', get_post_mime_type( $attach_id ) );
	}

	/**
	 * wp_update_post wrapper that prevents a post revision.
	 *
	 * @since  3.0.2
	 *
	 * @param  array        $post_data Array of post data.
	 *
	 * @return int|WP_Error The value 0 or WP_Error on failure. The post ID on success.
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
	 * @since  3.0.2
	 *
	 * @param  string $post_type Post type to check if `is_post_type_hierarchical`
	 *
	 * @return bool              Whether post type supports hierarchy.
	 */
	public function should_map_hierarchy( $post_type ) {
		return apply_filters( 'gc_map_hierarchy', is_post_type_hierarchical( $post_type ), $post_type, $this );
	}

	/**
	 * Add/create list of post/parent item ids to set WP hierarchy later.
	 *
	 * @since  3.0.2
	 *
	 * @param  int  $post_id WordPress post id to eventually update.
	 *
	 * @return void
	 */
	public function schedule_hierarchy_update( $post_id ) {

		$option = "gc_associate_hierarchy_{$this->mapping->ID}";

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
		if ( wp_next_scheduled( 'gc_associate_hierarchy', $args ) ) {
			wp_clear_scheduled_hook( 'gc_associate_hierarchy', $args );
		}

		/*
		 * Schedule an event to associate hierarchy for these posts.
		 * Will likely never be hit, as the gc_pull_complete event will take precedence.
		 */
		wp_schedule_single_event( time() + 60, 'gc_associate_hierarchy', $args );
	}

	/**
	 * Hooked into cron event, loops through list of pending hierarchies for given mapping,
	 * and attempts to set the parent post id based on the parent GC item.
	 *
	 * @since  3.0.2
	 *
	 * @param  int $mapping Mapping object or ID.
	 *
	 * @return void
	 */
	public static function associate_hierarchy( $mapping ) {
		$mapping = Mapping_Post::get( $mapping, true );
		if ( ! $mapping ) {
			return;
		}

		$mapping_id = $mapping->ID;

		$opt_name = "gc_associate_hierarchy_{$mapping_id}";
		$pending = get_option( $opt_name, array() );

		if ( ! empty( $pending ) && is_array( $pending ) ) {
			foreach ( $pending as $post_id => $parent_item_id ) {
				$post = get_post( absint( $post_id ) );

				if ( ! $post ) {
					continue;
				}

				$parent_post = \GatherContent\Importer\get_post_by_item_id( $parent_item_id, array(
					'post_type' => $post->post_type
				) );

				if ( ! $parent_post || ! isset( $parent_post->ID ) ) {
					continue;
				}

				// And update post (but don't create a revision for it).
				$post_id = self::post_update_no_revision( array(
					'ID'          => $post->ID,
					'post_parent' => absint( $parent_post->ID ),
				) );
			}
		}

		// We'll want to clear any scheduled events, since we completed them.
		if ( wp_next_scheduled( 'gc_associate_hierarchy', array( $mapping_id ) ) ) {
			wp_clear_scheduled_hook( 'gc_associate_hierarchy', array( $mapping_id ) );
		}

		return delete_option( $opt_name );
	}

}
