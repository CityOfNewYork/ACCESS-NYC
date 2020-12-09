<?php

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

if ( is_multisite() ) {
	global $wpdb;

	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
	if ( $blogs ) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			foreach ( wp_load_alloptions() as $option => $value ) {
				if ( strpos( $option, 'wpscan_' ) === 0 ) {
					delete_option( $option );
				}
			}
		}
		restore_current_blog();
	}
} else {
	foreach ( wp_load_alloptions() as $option => $value ) {
		if ( strpos( $option, 'wpscan_' ) === 0 ) {
			delete_option( $option );
		}
	}
}
