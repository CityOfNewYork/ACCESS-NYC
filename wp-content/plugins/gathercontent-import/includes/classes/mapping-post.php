<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;
use GatherContent\Importer\Post_Types\Template_Mappings;
use WP_Post;

/**
 * Mapping-post exception class w/ data property.
 *
 * @since 3.0.0
 */
class Mapping_Post_Exception extends Exception {}

/**
 * A wrapper for get_post which bundles a lot of mapping-specific functionality.
 *
 * @since 3.0.0
 */
class Mapping_Post extends Base {

	/**
	 * Array of Mapping_Post objects
	 *
	 * @var Mapping_Post[]
	 */
	protected static $instances;

	/**
	 * WP_Post object
	 *
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Array of mapping data or false.
	 *
	 * @var array|false
	 */
	protected $data = false;

	/**
	 * Get a Mapping_Post object instance by Post ID/object
	 *
	 * @since  3.0.0
	 *
	 * @param  WP_Post|int $post        WP_Post object or ID.
	 * @param  bool        $throw_error Request to throw an error on failure.
	 *
	 * @throws Exception If requesting to throw an error.
	 *
	 * @return Mapping_Post|false Will return false if $post is not found or not a template-mapping post.
	 */
	public static function get( $post, $throw_error = false ) {
		if ( $post instanceof Mapping_Post ) {
			return $post;
		}

		try {

			$post = self::get_post( $post );

			if ( ! isset( self::$instances[ $post->ID ] ) ) {
				self::$instances[ $post->ID ] = new self( $post );
			}

			return self::$instances[ $post->ID ];

		} catch ( \Exception $e ) {
			if ( $throw_error ) {
				throw $e;
			}
			return false;
		}
	}

	/**
	 * Get the full post object.
	 *
	 * @since  3.0.0
	 *
	 * @param  mixed $post Post id or object.
	 *
	 * @throws Mapping_Post_Exception If post could not be retrieved.
	 *
	 * @return WP_Post Post object.
	 */
	protected static function get_post( $post ) {
		$post = $post instanceof WP_Post ? $post : get_post( $post );

		if ( ! $post ) {
			throw new Mapping_Post_Exception( __CLASS__ . ' expects a WP_Post object or post ID.', __LINE__, $post );
		}

		if ( Template_Mappings::SLUG !== $post->post_type ) {
			throw new Mapping_Post_Exception( __CLASS__ . ' expects a ' . Template_Mappings::SLUG . ' object.', __LINE__, $post );
		}

		return $post;
	}

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 *
	 * @param WP_Post $post Post object.
	 */
	protected function __construct( WP_Post $post ) {
		$this->post = $post;
		$this->init_data( $post );
	}

	/**
	 * Initiate the data property from the post content.
	 *
	 * @since  3.0.0
	 *
	 * @param  WP_Post $post Post object.
	 *
	 * @return void
	 */
	protected function init_data( $post ) {
		if ( ! isset( $post->post_content ) || empty( $post->post_content ) ) {
			return;
		}

		$json = json_decode( $post->post_content, 1 );

		if ( is_array( $json ) ) {
			$this->data = $json;

			if ( isset( $this->data['mapping'] ) && is_array( $this->data['mapping'] ) ) {
				$_mapping = $this->data['mapping'];
				unset( $this->data['mapping'] );
				$this->data += $_mapping;
			}
		}
	}

	/**
	 * Get value from mapping data.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $arg     The arg key.
	 * @param  string $sub_arg Optional sub-arg key.
	 *
	 * @return mixed           Result of lookup.
	 */
	public function data( $arg = null, $sub_arg = null ) {
		if ( null === $arg ) {
			return $this->data;
		}

		if ( ! isset( $this->data[ $arg ] ) ) {
			return false;
		}

		$destination = $this->data[ $arg ];

		if ( isset( $destination['type'] ) ) {
			// Trim qualifiers (wpseo, acf, cmb2, etc).
			$type = explode( '--', $destination['type'] );
			$destination['type'] = $type[0];
		}

		if ( $sub_arg ) {
			return is_array( $destination ) && isset( $destination[ $sub_arg ] ) ? $destination[ $sub_arg ] : false;
		}

		return $destination;
	}

	/**
	 * Get the corresponding WP post status based on the item status id.
	 *
	 * @since  3.0.0
	 *
	 * @param  object $item Item object.
	 *
	 * @return mixed        WP post status or false.
	 */
	public function get_wp_status_for_item( $item ) {
		$status_id = isset( $item->custom_state_id ) ? $item->custom_state_id : $item;
		if ( $gc_status = $this->data( 'gc_status', $status_id ) ) {
			if ( ! empty( $gc_status['wp'] ) ) {
				return sanitize_text_field( $gc_status['wp'] );
			}
		}

		return false;
	}

	/**
	 * Get the status which the item should transition to.
	 *
	 * @since  3.0.0
	 *
	 * @param  object $item Item object.
	 *
	 * @return mixed        New item status or false.
	 */
	public function get_item_new_status( $item ) {
		$status_id = isset( $item->custom_state_id ) ? $item->custom_state_id : $item;
		if ( $gc_status = $this->data( 'gc_status', $status_id ) ) {
			if ( ! empty( $gc_status['after'] ) ) {
				return absint( $gc_status['after'] );
			}
		}
		return false;
	}

	/**
	 * Get the mapping edit link.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function get_edit_post_link() {
		return get_edit_post_link( $this->post->ID );
	}

	/**
	 * Wrapper for update_post_meta.
	 *
	 * @since  3.0.0
	 *
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	public function update_meta( $meta_key, $meta_value ) {
		return update_post_meta( $this->post->ID, $meta_key, $meta_value );
	}

	/**
	 * Wrapper for get_post_meta.
	 *
	 * @param string $meta_key The meta key to retrieve. By default, returns
	 *                         data for all keys. Default empty.
	 * @return mixed Will be an array if $single is false. Will be value of meta data
	 *               field if $single is true.
	 */
	public function get_meta( $meta_key ) {
		return get_post_meta( $this->post->ID, $meta_key, 1 );
	}

	/**
	 * Wrapper for delete_post_meta.
	 *
	 * @param string $meta_key Metadata name.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_meta( $meta_key ) {
		return delete_post_meta( $this->post->ID, $meta_key );
	}

	/**
	 * Gets the _gc_template meta value.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed
	 */
	public function get_template() {
		return $this->get_meta( '_gc_template' );
	}

	/**
	 * Gets the _gc_project meta value.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed
	 */
	public function get_project() {
		return $this->get_meta( '_gc_project' );
	}

	/**
	 * Gets the _gc_account_id meta value.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed
	 */
	public function get_account_id() {
		return $this->get_meta( '_gc_account_id' );
	}

	/**
	 * Gets the _gc_account meta value.
	 *
	 * @since  3.0.0
	 *
	 * @return mixed
	 */
	public function get_account_slug() {
		return $this->get_meta( '_gc_account' );
	}

	/**
	 * Gets the items to pull.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_items_to_pull() {
		return $this->get_items_to_sync();
	}

	/**
	 * Updates the items to pull.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $items Array of items to store to meta.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	public function update_items_to_pull( $items ) {
		return $this->update_items_to_sync( $items );
	}

	/**
	 * Gets the percent of items pulled.
	 *
	 * @since  3.0.0
	 *
	 * @return int
	 */
	public function get_pull_percent() {
		return $this->get_sync_percent();
	}

	/**
	 * Gets the items to push.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	public function get_items_to_push() {
		return $this->get_items_to_sync( 'push' );
	}

	/**
	 * Updates the items to pull.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $items Array of items to store to meta.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	public function update_items_to_push( $items ) {
		return $this->update_items_to_sync( $items, 'push' );
	}

	/**
	 * Gets the percent of items pushed.
	 *
	 * @since  3.0.0
	 *
	 * @return int
	 */
	public function get_push_percent() {
		return $this->get_sync_percent( 'push' );
	}

	/**
	 * Gets the items to sync, based on $direction
	 *
	 * @since  3.0.0
	 *
	 * @param  string $direction Push or pull.
	 *
	 * @return array
	 */
	public function get_items_to_sync( $direction = 'pull'  ) {
		$items = $this->get_meta( "_gc_{$direction}_items" );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Updates the items to sync, based on $direction.
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $items Array of items to store to meta.
	 * @param  string $direction Push or pull.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure.
	 */
	public function update_items_to_sync( $items, $direction = 'pull' ) {
		if ( empty( $items ) || empty( $items['pending'] ) ) {
			return $this->delete_meta( "_gc_{$direction}_items" );
		}

		return $this->update_meta( "_gc_{$direction}_items", $items );
	}

	/**
	 * Gets the percent of items synced.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $direction Push or pull.
	 *
	 * @return int
	 */
	public function get_sync_percent( $direction = 'pull'  ) {
		$percent = 1;

		$items = $this->get_items_to_sync( $direction );

		if ( ! empty( $items ) ) {

			if ( empty( $items['pending'] ) ) {
				$this->delete_meta( "_gc_{$direction}_items" );
			} else {

				$pending_count = count( $items['pending'] );
				$done_count = ! empty( $items['complete'] ) ? count( $items['complete'] ) : 0;

				$percent = $done_count / ( $pending_count + $done_count );
			}
		}

		return round( $percent * 100 );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @param  string $property The class property to get.
	 * @throws Mapping_Post_Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $property ) {

		switch ( $property ) {
			case 'post':
			case 'data':
				return $this->{$property}();

			default:
				// Check post object for property
				// In general, we'll avoid using same-named properties,
				// so the post object properties are always available.
				if ( isset( $this->post->{$property} ) ) {
					return $this->post->{$property};
				}
				throw new Mapping_Post_Exception( 'Invalid ' . __CLASS__ . ' property: ' . $property );
		}
	}

	/**
	 * Magic isset checker for our object.
	 *
	 * @param string $property The class property to check if isset.
	 * @return bool
	 */
	public function __isset( $property ) {
		// Check post object for property
		// In general, we'll avoid using same-named properties,
		// so the post object properties are always available.
		return isset( $this->post->{$property} );
	}

}
