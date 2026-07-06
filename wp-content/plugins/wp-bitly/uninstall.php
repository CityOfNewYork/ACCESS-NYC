<?php
/**
 * Uninstall routines for WP Bitly.
 *
 * @package   wp-bitly
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/wp-bitly
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! defined( 'WPBITLY_OPTIONS' ) ) {
	define( 'WPBITLY_OPTIONS', 'wpbitly-options' );
}
if ( ! defined( 'WPBITLY_AUTHORIZED' ) ) {
	define( 'WPBITLY_AUTHORIZED', 'wpbitly-authorized' );
}

/**
 * Remove all plugin data on uninstall.
 *
 * @return void
 */
function wpbitly_uninstall() {
	// Delete associated options.
	delete_option( WPBITLY_OPTIONS );
	delete_option( WPBITLY_AUTHORIZED );

	// Grab all posts with an attached shortlink.
	$posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'any',
			'meta_key'    => '_wpbitly', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);

	// And remove our meta information from them.
	foreach ( $posts as $post ) {
		delete_post_meta( $post->ID, '_wpbitly' );
	}
}
wpbitly_uninstall();
