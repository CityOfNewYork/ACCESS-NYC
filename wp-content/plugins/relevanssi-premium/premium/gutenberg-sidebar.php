<?php
/**
 * /premium/gutenberg-sidebar.php
 *
 * The Gutenberg sidebar for Relevanssi Premium.
 *
 * @package Relevanssi_Premium
 * @author  Mikko Saari
 * @license https://wordpress.org/about/gpl/ GNU General Public License
 * @see     https://www.relevanssi.com/
 */

add_action( 'init', 'relevanssi_register_gutenberg_meta' );
add_action( 'enqueue_block_editor_assets', 'relevanssi_block_editor_assets' );
add_action( 'rest_api_init', 'relevanssi_register_gutenberg_rest_routes' );
add_filter( 'load_script_translation_file', 'relevanssi_rename_translation_file', 10, 2 );

/**
 * Registers the meta fields for the block editor.
 *
 * Attached to the 'init' custom hook, this function will register all the
 * necessary meta fields so that they are available in the block editor.
 *
 * @since 2.5.0
 */
function relevanssi_register_gutenberg_meta() {
	$relevanssi_meta_fields = array(
		array(
			'meta_key'    => '_relevanssi_hide_post',
			'description' => 'Hide this post',
		),
		array(
			'meta_key'    => '_relevanssi_hide_content',
			'description' => 'Hide post content',
		),
		array(
			'meta_key'    => '_relevanssi_pin_for_all',
			'description' => 'Pin for all searches',
		),
		array(
			'meta_key'    => '_relevanssi_pin_keywords',
			'description' => 'Pin for these keywords',
		),
		array(
			'meta_key'    => '_relevanssi_unpin_keywords',
			'description' => 'Block for these keywords',
		),
		array(
			'meta_key'    => '_relevanssi_related_keywords',
			'description' => 'Keywords for related posts searches',
		),
		array(
			'meta_key'    => '_relevanssi_related_include_ids',
			'description' => 'Post IDs for included related posts',
		),
		array(
			'meta_key'    => '_relevanssi_related_exclude_ids',
			'description' => 'Post IDs for excluded related posts',
		),
		array(
			'meta_key'    => '_relevanssi_related_no_append',
			'description' => "Don't append related posts to this post",
		),
		array(
			'meta_key'    => '_relevanssi_related_not_related',
			'description' => 'Disable related posts for this post',
		),
		array(
			'meta_key'    => '_relevanssi_related_posts',
			'description' => 'Related posts for this post',
		),
		array(
			'meta_key'    => '_relevanssi_noindex_reason',
			'description' => 'Reason this post is not indexed',
		),
	);

	foreach ( $relevanssi_meta_fields as $meta_field ) {
		register_meta(
			'post',
			$meta_field['meta_key'],
			array(
				'type'          => 'string',
				'description'   => $meta_field['description'],
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => '__return_true',
			)
		);
	}
}

/**
 * Registers the Gutenberg sidebar script.
 *
 * Registers the Gutenberg sidebar script, exact version depending on whether
 * the RELEVANSSI_DEVELOP constant is set or not, includes the dependencies and
 * the translations.
 *
 * @since 2.5.0
 */
function relevanssi_register_gutenberg_script() {
	global $relevanssi_variables;

	if ( ! function_exists( 'wp_set_script_translations' ) ) {
		return;
	}

	global $post;
	if ( ! $post ) {
		return;
	}
	if ( $post && ! post_type_supports( $post->post_type, 'custom-fields' ) ) {
		return;
	}

	if ( ! current_user_can(
		/**
		 * Filters the capability required to access the Relevanssi sidebar.
		 *
		 * @param string The capability required, default 'edit_others_posts'.
		 */
		apply_filters( 'relevanssi_sidebar_capability', $relevanssi_variables['sidebar_capability'] )
	)
	) {
		return;
	}

	$show_post_controls = true;
	if ( 'on' === get_option( 'relevanssi_hide_post_controls' ) ) {
		$show_post_controls = false;
		/**
		 * Adjusts the capability required to show the Relevanssi post controls
		 * for admins.
		 *
		 * @param string $capability The minimum capability required, default
		 * 'manage_options'.
		 */
		if (
			'on' === get_option( 'relevanssi_show_post_controls' ) &&
			current_user_can( apply_filters( 'relevanssi_options_capability', 'manage_options' ) )
			) {
			$show_post_controls = true;
		}
	}
	if ( ! $show_post_controls ) {
		return;
	}

	$file_location = 'premium/gutenberg-sidebar/';
	if ( RELEVANSSI_DEVELOP ) {
		$file_location = 'build/';
	}
	wp_register_script(
		'relevanssi-sidebar',
		plugin_dir_url( $relevanssi_variables['plugin_basename'] ) . $file_location . 'index.js',
		array( 'wp-api-fetch', 'wp-i18n', 'wp-blocks', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-edit-post' ),
		1,
		true
	);
	wp_set_script_translations( 'relevanssi-sidebar', 'relevanssi', WP_CONTENT_DIR . '/languages/plugins' );
}

/**
 * Enqueues the Gutenberg sidebar script.
 *
 * @since 2.5.0
 */
function relevanssi_block_editor_assets() {
	relevanssi_register_gutenberg_script();
	wp_enqueue_script( 'relevanssi-sidebar' );
}

/**
 * Adds a REST API endpoint for "How Relevanssi sees this post".
 *
 * Takes in a post ID and returns the "How Relevanssi sees this post" data for
 * that post.
 *
 * @param array $data The post ID in $data['id'].
 *
 * @return array The indexed terms for various parts of the post in an
 * associative array.
 *
 * @uses relevanssi_fetch_sees_data()
 *
 * @since 2.5.0
 */
function relevanssi_sees_post_endpoint( $data ) {
	return relevanssi_fetch_sees_data( $data['id'] );
}

/**
 * Adds a REST API endpoint for listing the related posts.
 *
 * @param array $data The post ID in $data['id'].
 *
 * @return array The related posts in an array format (id, URL and title).
 *
 * @since 2.5.0
 */
function relevanssi_list_related_posts_endpoint( $data ) {
	return relevanssi_generate_related_list( $data['id'], 'ARRAY' );
}

/**
 * Adds a REST API endpoint for listing the excluded related posts.
 *
 * @param array $data The post ID in $data['id'].
 *
 * @return array The excluded posts in an array format (id, URL and title).
 *
 * @since 2.5.0
 */
function relevanssi_list_excluded_posts_endpoint( $data ) {
	return relevanssi_generate_excluded_list( $data['id'], 'ARRAY' );
}

/**
 * Adds a REST API endpoint for excluding a post from related posts.
 *
 * @param array $data The post ID in $data['post_id'], the ID of the post to
 * exclude in $data['exclude'].
 *
 * @return array The excluded posts in an array format (id, URL and title).
 *
 * @since 2.5.0
 */
function relevanssi_exclude_related_post_endpoint( $data ) {
	relevanssi_exclude_a_related_post( $data['post_id'], $data['exclude'] );
	return relevanssi_generate_excluded_list( $data['post_id'], 'ARRAY' );
}

/**
 * Adds a REST API endpoint for unexcluding a post from related posts.
 *
 * @param array $data The post ID in $data['post_id'], the ID of the post to
 * return in $data['return'].
 *
 * @return array The excluded posts in an array format (id, URL and title).
 *
 * @since 2.5.0
 */
function relevanssi_unexclude_related_post_endpoint( $data ) {
	relevanssi_unexclude_a_related_post( $data['post_id'], $data['return'] );
	return relevanssi_generate_excluded_list( $data['post_id'], 'ARRAY' );
}

/**
 * Adds a REST API endpoint for regenerating the related posts list.
 *
 * This is triggered when either the keywords or the post ID list for the
 * related posts is changed. This will get the key and the value of the changed
 * meta field as a parameter and will either empty or update the meta field and
 * then trigger related posts list regeneration.
 *
 * @param array $data The post ID in $data['id'], the meta key name in
 * $data['meta_key'] and the new value in $data['meta_value'].
 *
 * @return array The related posts in an array format (id, URL and title).
 *
 * @since 2.5.0
 */
function relevanssi_regenerate_related_endpoint( $data ) {
	if ( 0 === $data['meta_value'] ) {
		delete_post_meta( $data['id'], $data['meta_key'] );
	} else {
		update_post_meta( $data['id'], $data['meta_key'], $data['meta_value'] );
	}
	delete_post_meta( $data['id'], '_relevanssi_related_posts' );
	return relevanssi_generate_related_list( $data['id'], 'ARRAY' );
}

/**
 * Adds a REST API endpoint for listing common search terms.
 *
 * @param array $data The post ID in $data['id'].
 *
 * @return array The common terms in an array format (id, query, and count).
 *
 * @since 2.5.0
 */
function relevanssi_list_insights_common_terms( $data ) {
	return relevanssi_generate_tracking_insights_most_common( $data['id'], 'ARRAY' );
}

/**
 * Adds a REST API endpoint for listing low-ranking search terms.
 *
 * @param array $data The post ID in $data['id'].
 *
 * @return array The terms in an array format (id, query, rank).
 *
 * @since 2.5.0
 */
function relevanssi_list_insights_low_ranking_terms( $data ) {
	return relevanssi_generate_tracking_insights_low_ranking( $data['id'], 'ARRAY' );
}

/**
 * Registers the REST API endpoints.
 *
 * @see register_rest_route()
 *
 * @since 2.5.0
 */
function relevanssi_register_gutenberg_rest_routes() {
	$routes = array(
		array(
			'path'     => '/excluderelatedpost/(?P<exclude>\d+)/(?P<post_id>\d+)',
			'callback' => 'relevanssi_exclude_related_post_endpoint',
			'args'     => array(
				'exclude' => 'numeric',
				'post_id' => 'numeric',
			),
		),
		array(
			'path'     => '/unexcluderelatedpost/(?P<return>\d+)/(?P<post_id>\d+)',
			'callback' => 'relevanssi_unexclude_related_post_endpoint',
			'args'     => array(
				'return'  => 'numeric',
				'post_id' => 'numeric',
			),
		),
		array(
			'path'     => '/listexcluded/(?P<id>\d+)',
			'callback' => 'relevanssi_list_excluded_posts_endpoint',
			'args'     => array(
				'id' => 'numeric',
			),
		),
		array(
			'path'     => '/listrelated/(?P<id>\d+)',
			'callback' => 'relevanssi_list_related_posts_endpoint',
			'args'     => array(
				'id' => 'numeric',
			),
		),
		array(
			'path'     => '/sees/(?P<id>\d+)',
			'callback' => 'relevanssi_sees_post_endpoint',
			'args'     => array(
				'id' => 'numeric',
			),
		),
		array(
			'path'     => '/regeneraterelatedposts/(?P<id>\d+)/(?P<meta_key>\w+)/(?P<meta_value>[^/]+)',
			'callback' => 'relevanssi_regenerate_related_endpoint',
			'args'     => array(
				'id'         => 'numeric',
				'meta_key'   => 'metakey',
				'meta_value' => 'urldecode',
			),
		),
		array(
			'path'     => '/listinsightscommon/(?P<id>\d+)',
			'callback' => 'relevanssi_list_insights_common_terms',
			'args'     => array(
				'id' => 'numeric',
			),
		),
		array(
			'path'     => '/listinsightslowranking/(?P<id>\d+)',
			'callback' => 'relevanssi_list_insights_low_ranking_terms',
			'args'     => array(
				'id' => 'numeric',
			),
		),
	);

	foreach ( $routes as $route ) {
		$args = array();
		foreach ( $route['args'] as $name => $type ) {
			switch ( $type ) {
				case 'metakey':
					$args[ $name ] = array(
						'validate_callback' => function ( $param ) {
							return in_array( $param, array( '_relevanssi_related_keywords', '_relevanssi_related_include_ids' ), true );
						},
					);
					break;
				case 'urldecode':
					$args[ $name ] = array(
						'sanitize_callback' => function ( $param ) {
							return urldecode( $param );
						},
					);
					break;
				case 'numeric':
				default:
					$args[ $name ] = array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					);
			}
		}

		register_rest_route(
			'relevanssi/v1',
			$route['path'],
			array(
				'methods'             => 'GET',
				'callback'            => $route['callback'],
				'args'                => $args,
				'permission_callback' => function () {
					global $relevanssi_variables;
					// Filter documented in /premium/gutenberg-sidebar.php.
					return current_user_can( apply_filters( 'relevanssi_sidebar_capability', $relevanssi_variables['sidebar_capability'] ) );
				},
			)
		);
	}
}

/**
 * Rename the Relevanssi Gutenberg sidebar translation file.
 *
 * WordPress assumes the file name is relevanssi-LOCALE-relevanssi-sidebar.json,
 * but the file from TranslationsPress is relevanssi-LOCALE.json. We rename the
 * file WP is looking for here.
 *
 * @param string $file   The original file name.
 * @param string $handle The script handle.
 *
 * @return string The corrected filename.
 */
function relevanssi_rename_translation_file( $file, $handle ) {
	if ( 'relevanssi-sidebar' !== $handle ) {
		return $file;
	}
	return str_replace( '-relevanssi-sidebar', '', $file );
}
