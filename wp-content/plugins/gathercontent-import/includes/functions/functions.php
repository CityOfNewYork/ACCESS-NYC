<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;
use WP_Query;

/**
 * Style enqueue helper w/ GC defaults.
 *
 * @since  3.0.0
 *
 * @param string           $handle   Name of the stylesheet. Should be unique.
 * @param string           $filename Path (w/o extension/suffix) to CSS file in /assets/css/.
 * @param array            $deps     Optional. An array of registered stylesheet handles this stylesheet
 *                                   depends on. Default empty array.
 * @param string|bool|null $ver      Optional. String specifying stylesheet version number,
 *                                   if it has one, which is added to the URL.
 *
 * @return void
 */
function enqueue_style( $handle, $filename, $deps = array(), $ver = GATHERCONTENT_ENQUEUE_VERSION ) {
	$suffix = Utils::asset_suffix();
	wp_enqueue_style( $handle, GATHERCONTENT_URL . "assets/css/{$filename}{$suffix}.css", $deps, $ver );
}

/**
 * Script enqueue helper w/ GC defaults.
 *
 * @since  3.0.0
 *
 * @param string           $handle   Name of the script. Should be unique.
 * @param string           $filename Path (w/o extension/suffix) to JS file in /assets/js/.
 * @param array            $deps     Optional. An array of registered script handles this
 *                                   script depends on. Default empty array.
 * @param string|bool|null $ver      Optional. String specifying script version number,
 *                                   if it has one, which is added to the URL.
 *
 * @return void
 */
function enqueue_script( $handle, $filename, $deps = array(), $ver = GATHERCONTENT_ENQUEUE_VERSION ) {
	$suffix = Utils::asset_suffix();
	wp_enqueue_script( $handle, GATHERCONTENT_URL . "assets/js/{$filename}{$suffix}.js", $deps, $ver, 1 );
}

/**
 * Wrapper for WP_Query that gets the assocated post for a GatherContent Item Id.
 *
 * @since  3.0.0
 *
 * @param  int   $item_id GatherContent Item Id.
 * @param  array $args    Optional array of WP_Query args.
 *
 * @return mixed          WP_Post if an associated post is found.
 */
function get_post_by_item_id( $item_id, $args = array() ) {
	global $wpml_query_filter;
	if ( is_object( $wpml_query_filter ) ) {
		// We do not want wpml messing with our queries here.
		remove_filter( 'posts_join', array( $wpml_query_filter, 'posts_join_filter' ), 10, 2 );
		remove_filter( 'posts_where', array( $wpml_query_filter, 'posts_where_filter' ), 10, 2 );
	}

	$query = new WP_Query( wp_parse_args( $args, array(
		'post_type'      => \GatherContent\Importer\available_mapping_post_types(),
		'posts_per_page' => 1,
		'no_found_rows'  => true,
		// @codingStandardsIgnoreStart
		'meta_query'     => array(
			array(
				'key'   => '_gc_mapped_item_id',
				'value' => $item_id,
			),
		),
		// @codingStandardsIgnoreEnd
	) ) );

	return $query->have_posts() && $query->post ? $query->post : false;
}

/**
 * Wrapper for get_post_meta that gets the associated GatherContent item ID, if it exists.
 *
 * @since  3.0.0
 *
 * @param  int $post_id The ID of the post to check.
 *
 * @return mixed         Result of get_post_meta.
 */
function get_post_item_id( $post_id ) {
	return get_post_meta( $post_id, '_gc_mapped_item_id', 1 );
}

/**
 * Wrapper for update_post_meta that saves the associated GatherContent item ID to the post's meta.
 *
 * @since  3.0.0
 *
 * @param  int $post_id The ID of the post to store the item ID against.
 * @param  int $item_id The item id to store against the post.
 *
 * @return mixed         Result of update_post_meta.
 */
function update_post_item_id( $post_id, $item_id ) {
	return update_post_meta( $post_id, '_gc_mapped_item_id', $item_id );
}

/**
 * Wrapper for get_post_meta that gets the associated GatherContent item meta, if it exists.
 *
 * @since  3.0.0
 *
 * @param  int $post_id The ID of the post to check.
 *
 * @return mixed         Result of get_post_meta.
 */
function get_post_item_meta( $post_id ) {
	$meta = get_post_meta( $post_id, '_gc_mapped_meta', 1 );
	return $meta;
}

/**
 * Wrapper for update_post_meta that saves the associated GatherContent item meta to the post's meta.
 *
 * @since  3.0.0
 *
 * @param  int   $post_id The ID of the post to update.
 * @param  mixed $meta    The item meta to store against the post.
 *
 * @return mixed          Result of update_post_meta.
 */
function update_post_item_meta( $post_id, $meta ) {
	return update_post_meta( $post_id, '_gc_mapped_meta', $meta );
}

/**
 * Wrapper for get_post_meta that gets the associated GatherContent mapping post ID, if it exists.
 *
 * @since  3.0.0
 *
 * @param  int $post_id The ID of the post to check.
 *
 * @return mixed Result of get_post_meta.
 */
function get_post_mapping_id( $post_id ) {
	return get_post_meta( $post_id, '_gc_mapping_id', 1 );
}

/**
 * Wrapper for update_post_meta that saves the associated GatherContent mapping post ID to the post's meta.
 *
 * @since  3.0.0
 *
 * @param  int $post_id The ID of the post to update.
 * @param  int $mapping_post_id The ID of the mapping post.
 *
 * @return mixed Result of update_post_meta.
 */
function update_post_mapping_id( $post_id, $mapping_post_id ) {
	return update_post_meta( $post_id, '_gc_mapping_id', $mapping_post_id );
}

/**
 * Augment a GatherContent item object with additional data for JS templating.
 *
 * @since  3.0.0
 *
 * @param  object $item       GatherContent item object.
 * @param  int    $mapping_id Optional. ID of the mapping post.
 *
 * @return array              Object prepared for JS.
 */
function prepare_item_for_js( $item, $mapping_id = 0 ) {
	$post = \GatherContent\Importer\get_post_by_item_id( $item->id );

	$js_item = (array) $item;
	$js_item['mapping'] = $mapping_id;

	if ( $post ) {
		$js_item['post_id']    = $post->ID;
		$js_item['editLink']   = get_edit_post_link( $post->ID );
		$js_item['post_title'] = get_the_title( $post->ID );
		$js_item['current']    = \GatherContent\Importer\post_is_current( $post->ID, $item );
		$js_item['ptLabel']    = \GatherContent\Importer\get_post_type_singular_label( $post );

		if ( ! $mapping_id ) {
			$js_item['mapping'] = \GatherContent\Importer\get_post_mapping_id( $post->ID );
		}
	}

	return \GatherContent\Importer\prepare_js_data( $js_item, $item, 'item' );
}

/**
 * Get a an array of data from a WP_Post object to be used as a backbone model.
 *
 * @since  3.0.0
 *
 * @param  mixed $post     WP_Post or post ID.
 * @param  bool  $uncached Whether to fetch item data uncached. Default is to ONLY fetch from cache.
 *
 * @return array           JS post array.
 */
function prepare_post_for_js( $post, $uncached = false ) {
	$post = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post ) {
		return false;
	}

	$js_post = array_change_key_case( (array) $post );

	$js_post['item']    = absint( \GatherContent\Importer\get_post_item_id( $post->ID ) );
	$js_post['mapping'] = absint( \GatherContent\Importer\get_post_mapping_id( $post->ID ) );
	$js_post['current'] = true;
	$js_post['post_id'] = $post->ID;
	$js_post['ptLabel'] = \GatherContent\Importer\get_post_type_singular_label( $post );

	if ( $js_post['item'] && ! $js_post['mapping'] || ! get_post( $js_post['mapping'] ) ) {
		$admin = General::get_instance()->admin;
		if ( isset( $admin->mapping_wizard->mappings ) ) {
			$js_post['mapping'] = $admin->mapping_wizard->mappings->get_by_item_id( $js_post['item'] );
			\GatherContent\Importer\update_post_mapping_id( $post->ID, $js_post['mapping'] );
		}
	}

	$item = null;
	if ( $js_post['item'] ) {
		$item = $uncached
			? General::get_instance()->api->uncached()->get_item( $js_post['item'] )
			: General::get_instance()->api->only_cached()->get_item( $js_post['item'] );
	}

	return \GatherContent\Importer\prepare_js_data( $js_post, $item );
}

/**
 * Get a an array of data from a WP_Post or GC item object to be used as a backbone model.
 *
 * @since  3.0.0
 *
 * @param  array  $args Array of args to be added to.
 * @param  object $item GatherContent item object.
 * @param  string $type Which type of data we are preparing, 'post' or 'item'.
 *
 * @return array        Array of modified args.
 */
function prepare_js_data( $args, $item = null, $type = 'post' ) {
	$args = wp_parse_args( $args, array(
		'item'        => 0,
		'itemName'    => __( 'N/A', 'gathercontent-importer' ),
		'mapping'     => 0,
		'post_id'     => 0,
		'mappingLink' => '',
		'mappingName' => __( '&mdash;', 'gathercontent-importer' ),
		'status'      => (object) array(),
		'itemName'    => __( 'N/A', 'gathercontent-importer' ),
		'updated_at'  => __( '&mdash;', 'gathercontent-importer' ),
		'editLink'    => '',
		'post_title'  => __( '&mdash;', 'gathercontent-importer' ),
		'ptLabel'     => __( 'Post', 'gathercontent-importer' ),
	) );

	if ( $mapping = Mapping_Post::get( $args['mapping'] ) ) {
		$args['mappingLink'] = get_edit_post_link( $mapping->ID );
		$account = $mapping->get_account_slug();
		$args['mappingName'] = $mapping->post_title . ( $account ? " ($account)" : '' );
	}

	if ( $item && isset( $item->id ) ) {
		$args['item'] = $item->id;
		if ( isset( $item->name ) ) {
			$args['itemName'] = $item->name;
		}

		$args['status'] = isset( $item->status->data )
			? $item->status->data
			: (object) array();

		$args['typeName'] = isset( $item->type )
			? Utils::gc_field_type_name( $item->type )
			: '';

		if ( isset( $item->updated_at->date ) ) {
			$args['updated_at'] = Utils::relative_date( $item->updated_at->date );
		}
	}

	if ( $args['post_id'] ) {
		$args['editLink'] = get_edit_post_link( $args['post_id'] );
	}

	if ( $args['post_id'] && $item ) {
		$args['current'] = \GatherContent\Importer\post_is_current( $args['post_id'], $item );
	}

	return apply_filters( "gc_prepare_js_data_for_$type", $args, $type, $item );
}

/**
 * Gets the singular label for a post's post-type object.
 *
 * @since  3.0.0
 *
 * @param  mixed  $post WP_Post
 *
 * @return string       Singular post-type label.
 */
function get_post_type_singular_label( $post ) {
	$label = __( 'Post', 'gathercontent-importer' );
	if ( ! isset( $post->post_type ) ) {
		return $label;
	}
	$object = get_post_type_object( $post->post_type );

	return isset( $object->labels->singular_name )
		? $object->labels->singular_name
		: $object->labels->name;
}

/**
 * Checks to see if a post is current with a GatherContent item.
 *
 * @since  3.0.0
 *
 * @param  int   $post_id Post ID.
 * @param  mixed $item    GatherContent item object.
 *
 * @return bool           Whether post is current.
 */
function post_is_current( $post_id, $item ) {
	$meta = \GatherContent\Importer\get_post_item_meta( $post_id );
	// Default, no.
	$is_current = false;

	if ( ! empty( $meta['updated_at'] ) ) {

		if ( isset( $item->updated_at->date ) ) {

			if ( is_object( $meta['updated_at'] ) ) {
				$meta['updated_at'] = $meta['updated_at']->date;
			}

			// Allowance of 10 milliseconds because of some possible race conditions.
			$is_current = Utils::date_current_with( $meta['updated_at'], $item->updated_at->date, 10 );
		} else {
			// If we couldn't find an item date, then we'll say, yes, we're current.
			$is_current = true;
		}
	}

	return $is_current;
}

/**
 * A button for flushing the cached connection to GC's API.
 *
 * @since  3.0.0
 *
 * @return string URL for flushing cache.
 */
function refresh_connection_link() {
	$args = array(
		'redirect_url' => false,
		'flush_url' => add_query_arg( array( 'flush_cache' => 1, 'redirect' => 1 ) ),
	);
	// @codingStandardsIgnoreStart
	if ( isset( $_GET['flush_cache'], $_GET['redirect'] ) ) {
		// @codingStandardsIgnoreEnd
		update_option( 'gc-api-updated', 1, false );
		$args['redirect_url'] = remove_query_arg( 'flush_cache', remove_query_arg( 'redirect' ) );
	}

	$view = new Views\View( 'refresh-connection-button', $args );

	return $view->load( false );
}

/**
 * Determine if current user can view GC settings.
 *
 * @since  3.0.0
 *
 * @return bool Whether current user can view GC settings.
 */
function user_allowed() {
	return current_user_can( \GatherContent\Importer\view_capability() );
}

/**
 * Capability for user to be able to view GC settings.
 *
 * @since  3.0.0
 *
 * @return string Capability
 */
function view_capability() {
	return apply_filters( 'gathercontent_settings_view_capability', 'publish_pages' );
}

/**
 * The filtered list of post-types available for mapping to GC items.
 * Modify with the 'gathercontent_mapping_post_types' filter.
 *
 * @since  3.0.3
 *
 * @return array  Array of post-type slugs.
 */
function available_mapping_post_types() {
	$post_types = get_post_types( array( 'public' => true ) );
	return apply_filters( 'gathercontent_mapping_post_types', $post_types );
}

/**
 * Detect if HTTP Auth is enabled.
 *
 * @since  3.0.7
 *
 * @return string|bool The Auth username if enabled, or false.
 */
function auth_enabled() {
	if ( !empty( $_SERVER['REMOTE_USER'] ) ) {
		return $_SERVER['REMOTE_USER'];
	}

	foreach ( array(
		'PHP_AUTH_USER',
		'PHP_AUTH_PW',
		'HTTP_AUTHORIZATION',
	) as $var ) {
		if ( !empty( $_SERVER[ $var ] ) ) {
			return true;
		}
	}

	return false;
}
